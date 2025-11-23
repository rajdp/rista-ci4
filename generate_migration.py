#!/usr/bin/env python3
"""
Script to compare legacy.sql and newdb.sql and generate migration SQL
for MySQL 5.7
"""

import re
from collections import OrderedDict

def parse_table_definitions(sql_file):
    """Parse SQL file and extract table definitions"""
    tables = OrderedDict()
    
    with open(sql_file, 'r', encoding='utf-8', errors='ignore') as f:
        content = f.read()
    
    # Find all CREATE TABLE statements with complete ENGINE clause
    # Pattern matches: CREATE TABLE ... ( ... ) ENGINE=...;
    pattern = r'CREATE TABLE\s+`?(\w+)`?\s*\((.*?)\)\s*(ENGINE\s*=\s*[^;]+);'
    
    for match in re.finditer(pattern, content, re.DOTALL | re.IGNORECASE):
        table_name = match.group(1)
        table_body = match.group(2)
        engine_clause = match.group(3)
        
        # Reconstruct full CREATE TABLE statement
        full_def = f"CREATE TABLE `{table_name}` (\n{table_body}\n) {engine_clause};"
        tables[table_name] = full_def
    
    return tables

def extract_columns(table_def):
    """Extract column definitions from table CREATE statement"""
    columns = OrderedDict()
    
    # Find the content between CREATE TABLE and ENGINE
    match = re.search(r'CREATE TABLE[^(]*\((.*?)\)\s*ENGINE', table_def, re.DOTALL | re.IGNORECASE)
    if not match:
        return columns
    
    content = match.group(1).strip()
    
    # Split by comma, handling nested parentheses and quotes
    parts = []
    current = ""
    depth = 0
    in_string = False
    string_char = None
    
    i = 0
    while i < len(content):
        char = content[i]
        
        # Handle string literals
        if char in ("'", '"') and (i == 0 or content[i-1] != '\\'):
            if not in_string:
                in_string = True
                string_char = char
            elif char == string_char:
                in_string = False
                string_char = None
            current += char
        elif not in_string:
            if char == '(':
                depth += 1
                current += char
            elif char == ')':
                depth -= 1
                current += char
            elif char == ',' and depth == 0:
                part = current.strip()
                if part:
                    parts.append(part)
                current = ""
            else:
                current += char
        else:
            current += char
        i += 1
    
    if current.strip():
        parts.append(current.strip())
    
    # Parse each part
    for part in parts:
        part = part.strip()
        if not part:
            continue
        
        # Skip PRIMARY KEY, KEY, UNIQUE KEY, FOREIGN KEY, etc.
        if re.match(r'^(PRIMARY\s+KEY|KEY|UNIQUE\s+KEY|FOREIGN\s+KEY|INDEX|CONSTRAINT|UNIQUE)', part, re.IGNORECASE):
            continue
        
        # Extract column name and definition
        # Match: `column_name` or column_name followed by definition
        col_match = re.match(r'`?(\w+)`?\s+(.+)', part, re.DOTALL)
        if col_match:
            col_name = col_match.group(1)
            col_def = col_match.group(2).strip()
            # Remove trailing comma if present
            col_def = col_def.rstrip(',')
            columns[col_name] = col_def
    
    return columns

def compare_columns(legacy_cols, newdb_cols):
    """Compare columns and return differences"""
    changes = []
    
    # Check for new columns
    for col_name, col_def in newdb_cols.items():
        if col_name not in legacy_cols:
            changes.append({
                'type': 'ADD',
                'column': col_name,
                'definition': col_def
            })
    
    # Check for modified columns
    for col_name in legacy_cols:
        if col_name in newdb_cols:
            legacy_def = legacy_cols[col_name]
            newdb_def = newdb_cols[col_name]
            
            # Normalize whitespace for comparison
            legacy_norm = ' '.join(legacy_def.split())
            newdb_norm = ' '.join(newdb_def.split())
            
            if legacy_norm != newdb_norm:
                changes.append({
                    'type': 'MODIFY',
                    'column': col_name,
                    'definition': newdb_def
                })
    
    return changes

def generate_alter_statements(table_name, changes):
    """Generate ALTER TABLE statements"""
    if not changes:
        return []
    
    statements = []
    for change in changes:
        if change['type'] == 'ADD':
            statements.append(f"ALTER TABLE `{table_name}` ADD COLUMN `{change['column']}` {change['definition']};")
        elif change['type'] == 'MODIFY':
            statements.append(f"ALTER TABLE `{table_name}` MODIFY COLUMN `{change['column']}` {change['definition']};")
    
    return statements

def main():
    print("Parsing legacy.sql...")
    legacy_tables = parse_table_definitions('legacy.sql')
    print(f"Found {len(legacy_tables)} tables in legacy.sql")
    
    print("Parsing newdb.sql...")
    newdb_tables = parse_table_definitions('newdb.sql')
    print(f"Found {len(newdb_tables)} tables in newdb.sql")
    
    migration_statements = []
    migration_statements.append("-- Migration SQL to update legacy schema to newdb schema")
    migration_statements.append("-- Generated for MySQL 5.7")
    migration_statements.append("-- WARNING: Review and test this migration on a backup database first!")
    migration_statements.append("--")
    migration_statements.append("")
    migration_statements.append("SET FOREIGN_KEY_CHECKS=0;")
    migration_statements.append("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';")
    migration_statements.append("")
    
    # Compare existing tables
    tables_processed = 0
    total_changes = 0
    
    for table_name in legacy_tables:
        if table_name in newdb_tables:
            legacy_cols = extract_columns(legacy_tables[table_name])
            newdb_cols = extract_columns(newdb_tables[table_name])
            
            changes = compare_columns(legacy_cols, newdb_cols)
            if changes:
                migration_statements.append(f"-- Table: {table_name}")
                statements = generate_alter_statements(table_name, changes)
                migration_statements.extend(statements)
                migration_statements.append("")
                tables_processed += 1
                total_changes += len(changes)
    
    # Add new tables (CREATE TABLE statements)
    new_tables = [t for t in newdb_tables if t not in legacy_tables]
    if new_tables:
        migration_statements.append("-- ============================================")
        migration_statements.append("-- New tables to be created")
        migration_statements.append("-- ============================================")
        migration_statements.append("")
        for table_name in new_tables:
            migration_statements.append(f"-- CREATE TABLE for {table_name}")
            # Extract just the CREATE TABLE statement
            table_def = newdb_tables[table_name]
            # Ensure it ends with semicolon
            if not table_def.rstrip().endswith(';'):
                table_def = table_def + ";"
            migration_statements.append(table_def)
            migration_statements.append("")
    
    migration_statements.append("SET FOREIGN_KEY_CHECKS=1;")
    
    # Write migration file
    output = '\n'.join(migration_statements)
    with open('migration.sql', 'w', encoding='utf-8') as f:
        f.write(output)
    
    print(f"\nMigration file generated: migration.sql")
    print(f"Tables with changes: {tables_processed}")
    print(f"Total column changes: {total_changes}")
    print(f"New tables to create: {len(new_tables)}")

if __name__ == '__main__':
    main()

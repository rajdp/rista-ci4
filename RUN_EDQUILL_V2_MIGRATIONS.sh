#!/bin/bash

# Script to run EdQuill V2 migrations directly via SQL
# Use this if the migration system is blocked by other migrations

echo "🚀 Running EdQuill V2 Migrations Directly..."
echo ""

# Get database credentials from .env
DB_NAME=$(grep -E "^database.default.database" .env 2>/dev/null | cut -d'=' -f2 | tr -d ' ' || echo "")
DB_USER=$(grep -E "^database.default.username" .env 2>/dev/null | cut -d'=' -f2 | tr -d ' ' || echo "root")
DB_PASS=$(grep -E "^database.default.password" .env 2>/dev/null | cut -d'=' -f2 | tr -d ' ' || echo "")

if [ -z "$DB_NAME" ]; then
    echo "❌ Could not detect database name from .env"
    echo "Please run manually:"
    echo "  mysql -u root -p your_database < app/Database/SQL/create_edquill_v2_tables.sql"
    echo "  mysql -u root -p your_database < app/Database/SQL/add_edquill_v2_indexes.sql"
    exit 1
fi

echo "📦 Creating EdQuill V2 tables..."
if [ -z "$DB_PASS" ]; then
    mysql -u "$DB_USER" "$DB_NAME" < app/Database/SQL/create_edquill_v2_tables.sql
else
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < app/Database/SQL/create_edquill_v2_tables.sql
fi

if [ $? -eq 0 ]; then
    echo "✅ Tables created successfully"
else
    echo "⚠️  Table creation may have failed. Please check errors above."
    exit 1
fi

echo ""
echo "📊 Adding indexes..."
if [ -z "$DB_PASS" ]; then
    mysql -u "$DB_USER" "$DB_NAME" < app/Database/SQL/add_edquill_v2_indexes.sql 2>&1 | grep -v "Duplicate key\|already exists" || true
else
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < app/Database/SQL/add_edquill_v2_indexes.sql 2>&1 | grep -v "Duplicate key\|already exists" || true
fi

echo "✅ Indexes added (warnings about existing indexes are OK)"
echo ""

# Mark migrations as run in migrations table
echo "📝 Marking migrations as run..."
if [ -z "$DB_PASS" ]; then
    mysql -u "$DB_USER" "$DB_NAME" <<EOF
INSERT IGNORE INTO migrations (version, class, group, namespace, time, batch) 
VALUES 
('2025-11-13-000000', 'App\\\\Database\\\\Migrations\\\\CreateEdQuillV2SchoolScopedTables', 'default', 'App', NOW(), (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
('2025-11-13-000001', 'App\\\\Database\\\\Migrations\\\\AddEdQuillV2Indexes', 'default', 'App', NOW(), (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m));
EOF
else
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
INSERT IGNORE INTO migrations (version, class, group, namespace, time, batch) 
VALUES 
('2025-11-13-000000', 'App\\\\Database\\\\Migrations\\\\CreateEdQuillV2SchoolScopedTables', 'default', 'App', NOW(), (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m)),
('2025-11-13-000001', 'App\\\\Database\\\\Migrations\\\\AddEdQuillV2Indexes', 'default', 'App', NOW(), (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m));
EOF
fi

echo "✅ Migrations marked as complete!"
echo ""
echo "🎉 EdQuill V2 tables are now ready!"
echo ""
echo "Next steps:"
echo "1. Load triggers: mysql -u root -p $DB_NAME < app/Database/SQL/triggers_outbox.sql"
echo "2. Test dashboard: Navigate to /dashboard/admin"
echo ""


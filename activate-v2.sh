#!/bin/bash

# EdQuill V2 Activation Script
# This script activates all backend changes

echo "🚀 Activating EdQuill V2 Backend Changes..."
echo ""

# Step 1: Run Migrations
echo "📦 Step 1: Running database migrations..."
php spark migrate

if [ $? -ne 0 ]; then
    echo "❌ Migration failed. Please check errors above."
    exit 1
fi

echo "✅ Migrations completed"
echo ""

# Step 2: Load Triggers
echo "⚙️  Step 2: Loading database triggers..."

# Get database name from .env or config
DB_NAME=$(grep -E "^database.default.database" .env 2>/dev/null | cut -d'=' -f2 | tr -d ' ' || echo "")
DB_USER=$(grep -E "^database.default.username" .env 2>/dev/null | cut -d'=' -f2 | tr -d ' ' || echo "root")
DB_PASS=$(grep -E "^database.default.password" .env 2>/dev/null | cut -d'=' -f2 | tr -d ' ' || echo "")

if [ -z "$DB_NAME" ]; then
    echo "⚠️  Could not detect database name from .env"
    echo "Please run manually:"
    echo "  mysql -u root -p your_database < app/Database/SQL/triggers_outbox.sql"
else
    if [ -z "$DB_PASS" ]; then
        mysql -u "$DB_USER" "$DB_NAME" < app/Database/SQL/triggers_outbox.sql
    else
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < app/Database/SQL/triggers_outbox.sql
    fi
    
    if [ $? -eq 0 ]; then
        echo "✅ Triggers loaded successfully"
    else
        echo "⚠️  Trigger loading may have failed. Please verify manually."
    fi
fi

echo ""

# Step 3: Verify Tables
echo "🔍 Step 3: Verifying tables created..."
php spark db:table t_event_outbox 2>/dev/null && echo "✅ t_event_outbox exists" || echo "⚠️  t_event_outbox not found"
php spark db:table t_audit_log 2>/dev/null && echo "✅ t_audit_log exists" || echo "⚠️  t_audit_log not found"
php spark db:table t_feature_flag 2>/dev/null && echo "✅ t_feature_flag exists" || echo "⚠️  t_feature_flag not found"
php spark db:table t_message_template 2>/dev/null && echo "✅ t_message_template exists" || echo "⚠️  t_message_template not found"
php spark db:table t_message_log 2>/dev/null && echo "✅ t_message_log exists" || echo "⚠️  t_message_log not found"
php spark db:table t_marketing_kpi_daily 2>/dev/null && echo "✅ t_marketing_kpi_daily exists" || echo "⚠️  t_marketing_kpi_daily not found"
php spark db:table t_revenue_daily 2>/dev/null && echo "✅ t_revenue_daily exists" || echo "⚠️  t_revenue_daily not found"

echo ""
echo "✅ Backend activation complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Start outbox worker: php spark outbox:worker"
echo "2. Restart Angular dev server: cd ../edquill-web_angupgrade/web && npm start"
echo "3. Navigate to: http://localhost:8211/#/admin/dashboard"
echo ""


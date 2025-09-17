# KaizenDMS Deployment Guide

## Server Setup Instructions

### Step 1: Upload Files to Server
Upload these files to your server root directory (where your DMS application is hosted):

**Required Files:**
```
your-server-root/
├── config.php                     # Application configuration
├── .env                          # Environment variables
├── includes/
│   └── database.php              # Database connection class
├── simple_db_check.php           # Database diagnostic tool
├── check_dms_status.php          # Full status checker  
├── deploy_dms_tables.php         # Table deployment script
└── dms_master_tables_schema.sql  # Database schema
```

### Step 2: Run Database Diagnostics
Run these commands on your server via SSH or web terminal:

```bash
# Step 1: Basic diagnostic (checks files and database connection)
php simple_db_check.php

# Step 2: Full status check (shows existing tables)
php check_dms_status.php

# Step 3: Deploy master tables (creates missing tables)
php deploy_dms_tables.php
```

### Step 3: Expected Output

#### simple_db_check.php - Expected Success Output:
```
🔍 Simple DMS Database Check
============================

📁 File Check:
   config.php: ✅ Found
   database.php: ✅ Found
   .env file: ✅ Found

⚙️ Configuration Loading:
   ✅ Config loaded successfully
   ✅ DB_HOST: 162.214.80.31
   ✅ DB_NAME: kaizenap_flow_db
   ✅ DB_USER: kaizenap_flowdb_user
   ✅ DB_PASS: ********
   ✅ DB_PORT: 3306

🗄️ Database Class Loading:
   ✅ Database class loaded

🔗 Database Connection Test:
   ✅ Database connection successful
   ✅ Current database: kaizenap_flow_db

📋 DMS Tables Check:
   ❌ No DMS tables found
   💡 Run deployment script to create tables

📊 Summary:
   Database connection: ✅ Working
   DMS tables: 0 found

💡 Next steps:
   1. Run: php deploy_dms_tables.php
   2. This will create the missing master tables
```

#### deploy_dms_tables.php - Expected Success Output:
```
🏗️ KaizenDMS Master Tables Deployment
=====================================
✅ Configuration loaded
✅ Database class loaded
🔗 Connected to database: kaizenap_flow_db

📋 Checking existing DMS tables...
ℹ️ No existing DMS tables found. Creating all master tables.

🚀 Deploying DMS master tables...
✅ Executed statement [repeats for each table/insert]

✅ Deployment completed successfully!
   📊 Statements executed: 45
   ⏭️ Statements skipped: 0

🔍 Verifying deployment...
✅ dms_sites
✅ dms_departments
✅ dms_customers
✅ dms_suppliers
✅ dms_process_areas
✅ dms_document_types
✅ dms_languages
✅ dms_review_cycles
✅ dms_notification_templates
✅ dms_notification_channels

📊 Verification Summary:
   ✅ Tables created: 10
   ❌ Tables missing: 0

📈 DMS Table Statistics:
   📋 dms_sites                    |      2 rows |     0.00 MB | DMS-managed site/location master data
   📋 dms_departments             |      5 rows |     0.00 MB | DMS-managed department master data
   [... continues for all tables]

🎉 DMS master tables deployment completed successfully!
```

### Step 4: Troubleshooting

#### If Files Are Missing:
- Ensure you've uploaded all required files
- Check file permissions (files should be readable by web server)
- Verify file paths match your server structure

#### If Database Connection Fails:
```
❌ Database connection failed: SQLSTATE[HY000] [2002] Connection refused
💡 Possible causes: Database server not running, wrong host/port
```
**Solutions:**
- Verify database server is running
- Check DB_HOST and DB_PORT in .env file
- Test database connectivity from server

#### If Access Denied:
```
❌ Database connection failed: SQLSTATE[HY000] [1045] Access denied for user
💡 Possible causes: Wrong username/password, user doesn't have access
```
**Solutions:**
- Verify DB_USER and DB_PASS in .env file
- Ensure database user has proper permissions
- Check if user can access the database from server IP

#### If Database Not Found:
```
❌ Database connection failed: SQLSTATE[HY000] [1049] Unknown database
💡 Possible causes: Database doesn't exist, wrong database name
```
**Solutions:**
- Verify DB_NAME in .env file
- Ensure kaizenap_flow_db database exists
- Create database if missing

### Step 5: Verification
After successful deployment, run:

```bash
php check_dms_status.php
```

This should show all 10 DMS tables with sample data:
- dms_sites (2 records)
- dms_departments (5 records) 
- dms_customers (sample records)
- dms_suppliers (sample records)
- dms_process_areas (7 records)
- dms_document_types (7 records)
- dms_languages (4 records)
- dms_review_cycles (3 records)
- dms_notification_templates (2 sample templates)
- dms_notification_channels (2 sample channels)

### Step 6: Next Steps
Once tables are deployed successfully:

1. **Test DMS Application**: Verify your web application can connect to database
2. **Review Sample Data**: Check if sample master data meets your needs
3. **Customize Data**: Modify master data according to your requirements
4. **Begin Development**: Start Phase 1 DMS development with master tables ready

## File Structure on Server

**After deployment, your server should have:**
```
/your-dms-root/
├── config.php                    # ✅ Required
├── .env                         # ✅ Required  
├── includes/
│   └── database.php             # ✅ Required
├── simple_db_check.php          # 🛠️ Diagnostic tool
├── check_dms_status.php         # 🛠️ Status checker
├── deploy_dms_tables.php        # 🛠️ Deployment script
├── dms_master_tables_schema.sql # 📄 Schema reference
└── [your existing DMS files]    # 📱 Application files
```

## Database Tables Created

The deployment creates these 10 master tables in `kaizenap_flow_db`:

| Table | Purpose | Sample Records |
|-------|---------|----------------|
| `dms_sites` | Site/location management | B-75, G-44 |
| `dms_departments` | Department structure | QA, MFG, ENG, MAINT, SAFETY |
| `dms_customers` | Customer data | Ready for your data |
| `dms_suppliers` | Supplier qualification | Ready for your data |
| `dms_process_areas` | Process classification | WELD, STITCH, ASSY, QC, INSP, PAINT, MAINT |
| `dms_document_types` | Document types | POL, SOP, WI, FORM, DWG, PFMEA, CP |
| `dms_languages` | Language support | en, mr, hi, gu |
| `dms_review_cycles` | Review scheduling | ANNUAL, BIENNIAL, QUARTERLY |
| `dms_notification_templates` | Message templates | Sample WhatsApp/Email templates |
| `dms_notification_channels` | Communication channels | EMAIL_SMTP, WHATSAPP_MAIN |

All tables use the `dms_` prefix and integrate seamlessly with existing KaizenFlow infrastructure.
# Database Triggers

## log_application_created

Automatically creates activity log entries when internship applications are created.

### Manual Installation

If the migration failed due to insufficient privileges, run this command with a MySQL user that has SUPER or SYSTEM_VARIABLES_ADMIN privilege:

```bash
mysql -h 172.21.144.1 -u root -p intership_manager < database/triggers/log_application_created.sql
```

Or connect to MySQL and run:

```sql
SOURCE database/triggers/log_application_created.sql;
```

### Verify Trigger Installation

```sql
SHOW TRIGGERS LIKE 'applications';
```

### Remove Trigger

```sql
DROP TRIGGER IF EXISTS log_application_created;
```

### How It Works

When a new record is inserted into the `applications` table, the trigger:
1. Joins with `users`, `internships`, and `groups` tables to get names
2. Creates a descriptive log message in Latvian
3. Stores all relevant data in the `activity_logs` table as JSON

### Trigger vs Application-Level Logging

| Aspect | Trigger | Application Service |
|--------|---------|---------------------|
| Execution | Automatic on INSERT | Manual call required |
| Privileges | Needs SUPER privilege | No special privileges |
| Performance | Adds overhead to INSERT | Can be async/queued |
| Flexibility | Fixed logic | Can be customized per call |
| Testing | Harder to test | Easy to unit test |

**Recommendation:** Use application-level logging (`ActivityLogService`) for most cases. Use the trigger only if you need guaranteed logging even when applications are inserted directly into the database.

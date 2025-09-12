# KaizenFlow Master Data Request Format

## Overview
This document defines the standardized format for KaizenFlow modules to request new master data or integrate with existing master data from KaizenMaster.

## Request Format

### 1. Module Information
```json
{
  "module_info": {
    "module_name": "KaizenTasks",
    "module_version": "v1.2.0",
    "requester": {
      "name": "Development Team Lead",
      "email": "dev@kaizen.com",
      "date": "2025-01-15"
    },
    "integration_type": "direct_db_access" // Options: direct_db_access, api_based, hybrid
  }
}
```

### 2. Existing Master Data Review
```json
{
  "existing_masters_reviewed": {
    "reviewed_date": "2025-01-15",
    "reviewed_masters": [
      {
        "master_type": "sites",
        "status": "will_reuse", // Options: will_reuse, need_extension, not_suitable
        "reuse_fields": ["id", "name", "code", "address", "timezone"],
        "missing_requirements": ["operating_hours", "capacity_metrics"],
        "notes": "Will use for location mapping in task assignments"
      },
      {
        "master_type": "departments", 
        "status": "need_extension",
        "reuse_fields": ["id", "name", "code"],
        "missing_requirements": ["department_head_id", "escalation_matrix", "sla_defaults"],
        "notes": "Need additional fields for task escalation workflows"
      },
      {
        "master_type": "users",
        "status": "not_suitable",
        "reason": "KaizenAuth integration handles user management",
        "alternative_approach": "Direct KaizenAuth API integration"
      }
    ]
  }
}
```

### 3. New Master Data Requirements
```json
{
  "new_masters_required": [
    {
      "master_type": "task_categories",
      "business_justification": "Standardize task categorization across all modules",
      "category": "Process Management", // Organization & Identity, Business Partners, Process Management, etc.
      "description": "Categories for different types of tasks (Maintenance, Quality, Production, etc.)",
      "estimated_records": 50,
      "fields": [
        {
          "field_name": "category_code",
          "data_type": "VARCHAR(20)",
          "required": true,
          "unique": true,
          "description": "Unique category identifier (MAINT, QUAL, PROD)",
          "sample_values": ["MAINT", "QUAL", "PROD", "SAFE"]
        },
        {
          "field_name": "category_name", 
          "data_type": "VARCHAR(100)",
          "required": true,
          "description": "Human readable category name",
          "sample_values": ["Maintenance Tasks", "Quality Control", "Production Tasks"]
        },
        {
          "field_name": "default_priority",
          "data_type": "ENUM('Low','Medium','High','Critical')",
          "required": true,
          "default_value": "Medium",
          "description": "Default priority for tasks in this category"
        },
        {
          "field_name": "escalation_time_hours",
          "data_type": "INT",
          "required": true,
          "default_value": 24,
          "description": "Hours before task escalation"
        },
        {
          "field_name": "requires_approval",
          "data_type": "BOOLEAN",
          "required": true,
          "default_value": false,
          "description": "Whether tasks in this category need approval"
        }
      ],
      "relationships": [
        {
          "related_master": "departments",
          "relationship_type": "many_to_many",
          "description": "Categories can be used by multiple departments"
        }
      ],
      "usage_context": {
        "primary_use": "Task creation and classification",
        "frequency": "Every task creation",
        "integration_points": ["TaskEngine", "QualityModule", "MaintenanceModule"]
      }
    }
  ]
}
```

### 4. Extension Requirements for Existing Masters
```json
{
  "master_extensions": [
    {
      "master_type": "sites",
      "extension_reason": "Need operational data for task scheduling",
      "new_fields": [
        {
          "field_name": "operating_hours_start",
          "data_type": "TIME",
          "required": false,
          "description": "Site operating start time",
          "sample_values": ["06:00:00", "08:00:00"]
        },
        {
          "field_name": "operating_hours_end", 
          "data_type": "TIME",
          "required": false,
          "description": "Site operating end time",
          "sample_values": ["18:00:00", "22:00:00"]
        },
        {
          "field_name": "max_concurrent_tasks",
          "data_type": "INT",
          "required": false,
          "default_value": 10,
          "description": "Maximum tasks that can run simultaneously"
        }
      ],
      "impact_assessment": {
        "existing_data": "No impact on existing records",
        "existing_modules": "No breaking changes expected",
        "migration_required": false
      }
    }
  ]
}
```

### 5. Integration Requirements
```json
{
  "integration_requirements": {
    "access_method": "direct_db_access", // direct_db_access, api_based, hybrid
    "permissions_needed": [
      "sites.view",
      "sites.create", 
      "departments.view",
      "task_categories.view",
      "task_categories.create",
      "task_categories.edit"
    ],
    "real_time_sync": {
      "required": true,
      "trigger_events": ["create", "update", "delete"],
      "notification_method": "database_trigger" // Options: database_trigger, api_webhook, message_queue
    },
    "data_validation": {
      "custom_validations": [
        {
          "field": "task_categories.escalation_time_hours",
          "rule": "Must be between 1 and 168 hours",
          "validation_logic": "value >= 1 AND value <= 168"
        }
      ]
    }
  }
}
```

### 6. Testing and Validation Plan
```json
{
  "testing_plan": {
    "test_data_volume": {
      "task_categories": 25,
      "extended_sites": 5
    },
    "integration_tests": [
      "Create task_category and verify in TaskEngine",
      "Update site operating_hours and verify task scheduling",
      "Delete task_category and verify cascading effects"
    ],
    "performance_requirements": {
      "max_response_time": "200ms for read operations",
      "max_concurrent_users": 50,
      "data_retention": "All audit trails for 7 years"
    }
  }
}
```

### 7. Rollback and Maintenance Plan
```json
{
  "maintenance_plan": {
    "rollback_strategy": {
      "new_masters": "Can be dropped without data loss",
      "extended_masters": "New fields can be dropped, existing data preserved"
    },
    "ongoing_maintenance": {
      "data_seeding": "Module will provide seed data for task_categories",
      "documentation": "Module will maintain integration documentation",
      "support_contact": "dev@kaizen.com"
    }
  }
}
```

## Complete Request Template

```json
{
  "master_data_request": {
    "module_info": { /* As defined above */ },
    "existing_masters_reviewed": { /* As defined above */ },
    "new_masters_required": [ /* As defined above */ ],
    "master_extensions": [ /* As defined above */ ],
    "integration_requirements": { /* As defined above */ },
    "testing_plan": { /* As defined above */ },
    "maintenance_plan": { /* As defined above */ }
  }
}
```

## Submission Process

### Step 1: Analysis Phase
1. Module team reviews existing master data documentation
2. Identifies reusable masters vs. new requirements  
3. Completes the request format above
4. Submits to KaizenMaster team

### Step 2: Review Phase
1. KaizenMaster team reviews request for:
   - Data model consistency
   - Relationship integrity
   - Performance impact
   - Security implications
2. Provides feedback and approval/rejection

### Step 3: Implementation Phase  
1. KaizenMaster team implements approved changes
2. Module team integrates and tests
3. Joint validation and sign-off

### Step 4: Production Deployment
1. Database schema updates
2. Permission configuration
3. Module deployment
4. Monitoring and validation

## Response Format from KaizenMaster Team

```json
{
  "request_response": {
    "request_id": "REQ-2025-001",
    "status": "approved", // approved, rejected, needs_revision
    "reviewed_by": "KaizenMaster Development Team",
    "review_date": "2025-01-20",
    "approved_items": {
      "new_masters": ["task_categories"],
      "master_extensions": ["sites.operating_hours_start", "sites.operating_hours_end"],
      "permissions": ["task_categories.view", "task_categories.create"]
    },
    "rejected_items": [
      {
        "item": "sites.max_concurrent_tasks",
        "reason": "Business logic should remain in requesting module",
        "alternative": "Store as module-specific configuration"
      }
    ],
    "implementation_timeline": {
      "schema_changes": "2025-01-25",
      "permission_setup": "2025-01-26", 
      "testing_ready": "2025-01-27",
      "production_ready": "2025-02-01"
    },
    "technical_notes": [
      "task_categories will include audit fields as per standard",
      "Operating hours will be nullable for backward compatibility",
      "Module responsible for own data validation logic"
    ]
  }
}
```

## Guidelines for Module Teams

### Before Submitting Request:
1. **Thoroughly review existing masters** - Avoid duplication
2. **Follow KaizenMaster naming conventions** - snake_case, descriptive names
3. **Include comprehensive business justification** - Why is this needed?
4. **Consider data relationships** - How does this connect to existing data?
5. **Plan for scale** - Will this work with 10x current data volume?

### Best Practices:
1. **Reuse over create** - Extend existing masters when possible
2. **Keep it simple** - Complex relationships should be in your module
3. **Think long-term** - Masters should be stable and reusable
4. **Document everything** - Future modules will build on your work
5. **Test thoroughly** - Master data errors affect multiple modules

## Integration Patterns

### Pattern 1: Direct Database Access (Recommended)
- Module accesses master_* tables directly
- Uses existing permissions system
- Minimal latency, maximum flexibility

### Pattern 2: API-Based Access  
- KaizenMaster provides REST APIs
- Better for external integrations
- Additional overhead but better isolation

### Pattern 3: Hybrid Approach
- Read operations: Direct DB access
- Write operations: API calls  
- Best of both worlds for complex scenarios
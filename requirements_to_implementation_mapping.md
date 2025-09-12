# Requirements to Implementation Mapping

This document maps each requirement from `dms_requirements_summary.md` to the specific implementation actions and database tables in `dms_implementation_plan.md`.

---

## Basic Requirements (B-1 to B-8) - Minimum Viable Compliance

### B-1: Single Repository for All Controlled Documents
**Requirement:** Core clause 7.5 (ISO 9001/IATF 16949) - Single repository for all controlled docs (Policy, SOP, WI, PFMEA, CP, Drawings, Forms)

**Implementation Mapping:**
- **Primary Table:** `dms_documents` - Central document registry
- **Supporting Tables:** 
  - `master_doc_types` - Document type definitions (Policy, SOP, WI, PFMEA, CP, Drawing, Form)
  - `dms_revisions` - Version control for each document
  - `dms_categories` - Document categorization
- **Functions:** 
  - `DocumentRepository::store()` - Central storage with unique doc_id generation
  - `createDocument($data)` - Creates document record with metadata
  - `uploadDocumentFile($docId, $file)` - File storage with integrity checks
- **File Structure:** `/uploads/dms/YYYY-MM/DD_[doc_type]_[doc_id]_[title-slug]_[rev].pdf`
- **Implementation Phase:** Phase 1 (Week 2-3)

### B-2: Document Metadata & Auto-numbering
**Requirement:** Traceability and audit readiness - Doc metadata & auto-numbering (title, type, area, rev, owner, status)

**Implementation Mapping:**
- **Primary Table:** `dms_documents` with fields:
  - `doc_number` (auto-generated: DMS-2025-001234)
  - `title`, `doc_type_id`, `process_area_id`, `current_revision`
  - `owner_user_id`, `author_user_id`, `status`
- **Supporting Tables:**
  - `master_doc_types` - Numbering format templates
  - `master_process_areas` - Area classifications
- **Functions:**
  - `generateDocumentNumber($docTypeId, $year)` - Auto-increment by type/area/site
  - `updateDocumentMetadata($docId, $metadata)` - Metadata management
  - `validateMetadata($docType, $metadata)` - Validation rules
- **Implementation Phase:** Phase 1 (Week 2-3)

### B-3: Document Lifecycle with E-signature
**Requirement:** Ensures only validated info reaches shop floor - Draft → Review → Approved → Effective → Obsolete lifecycle with e-sign

**Implementation Mapping:**
- **Primary Tables:** 
  - `dms_documents.status` - ENUM('draft','review','approved','effective','obsolete')
  - `dms_lifecycle_history` - State transition tracking
  - `dms_approval_matrix` - Approval workflow rules
  - `dms_reviews` - Review records with e-signatures
- **Functions:**
  - `submitForReview($docId, $reviewers)` - Draft → Review transition
  - `approveDocument($docId, $approverId, $signature)` - Approval with e-signature
  - `rejectDocument($docId, $rejectorId, $reason)` - Rejection handling
  - `makeEffective($docId, $effectiveDate)` - Approved → Effective transition
  - `obsoleteDocument($docId, $reason, $replacementDocId)` - End-of-life management
- **KaizenTasks Integration:** Auto-creates `DOC_REVIEW` and `DOC_APPROVED` tasks
- **Implementation Phase:** Phase 1 (Week 2-3)

### B-4: Controlled PDF Output with Watermark & QR Code
**Requirement:** Prevents misuse of uncontrolled prints - Controlled PDF output with watermark & QR/Barcode

**Implementation Mapping:**
- **Primary Tables:**
  - `dms_controlled_copies` - Controlled copy tracking
  - `dms_revisions.file_hash` - File integrity verification
- **Functions:**
  - `generateControlledPDF($docId, $copyNumber, $issuedTo)` - PDF generation with controls
  - `addWatermarkAndQR($pdfPath, $watermarkText, $qrCodeData)` - PDF modification
  - `generateWatermarkText($document, $copyNumber, $issuedTo)` - Watermark content
  - `trackPrintRequest($docId, $userId, $reason)` - Print request logging
- **QR Code Content:** 
  ```json
  {
    "doc_id": "123",
    "doc_number": "DMS-2025-001", 
    "revision": "A",
    "copy_number": "CC-001",
    "verification_url": "APP_URL/verify?token=xyz"
  }
  ```
- **Implementation Phase:** Phase 1 (Week 3)

### B-5: Search & Filters
**Requirement:** Users must find the latest doc fast - Search & filters by id/title/area/type/rev/status

**Implementation Mapping:**
- **Primary Table:** `dms_documents` with indexes:
  - `idx_status`, `idx_doc_type`, `idx_area`, `idx_owner`
- **Functions:**
  - `searchByDocNumber($docNumber)` - Exact/partial document number search
  - `searchByTitle($title)` - Full-text search in title
  - `filterByArea($areaId)` - Process area filtering
  - `filterByType($typeId)` - Document type filtering
  - `filterByStatus($status)` - Lifecycle status filtering
  - `filterByDateRange($startDate, $endDate, $dateType)` - Date-based filtering
  - `buildAdvancedQuery($filters)` - Combined filter queries
- **Search Features:** Full-text search, wildcard support, relevance ranking
- **Implementation Phase:** Phase 1 (Week 2-3)

### B-6: Immutable Audit Trail
**Requirement:** Mandatory for third-party audits - Immutable audit trail (who/what/when)

**Implementation Mapping:**
- **Primary Table:** `dms_audit_log` with fields:
  - `entity_type`, `entity_id`, `action`, `old_value`, `new_value`
  - `user_id`, `user_ip`, `user_agent`, `performed_at`
- **Functions:**
  - `logDocumentAction($docId, $action, $oldValue, $newValue)` - Action logging
  - `getDocumentHistory($docId)` - Complete document history
  - `getAuditTrail($filters)` - Filtered audit queries
  - `verifyAuditIntegrity($auditId)` - Tamper detection
  - `exportAuditLog($dateRange, $format)` - External audit support
- **Features:** No deletion capability, hash-based integrity, sequential numbering
- **Implementation Phase:** Phase 1 (Week 3)

### B-7: Retention Rules
**Requirement:** Meets legal & IATF retention - Retention rules (≥3 yrs, ≥10 yrs Safety/PPAP)

**Implementation Mapping:**
- **Primary Tables:**
  - `master_retention_classes` - Retention policies (Standard: 3yr, Safety: 10yr, PPAP: 15yr)
  - `dms_documents.retention_class_id` - Document retention assignment
- **Functions:**
  - `applyRetentionPolicy($docId)` - Automatic retention calculation
  - `checkRetentionExpiry()` - Daily/weekly expiry checking
  - `archiveDocument($docId)` - Archive process
  - `scheduleDocumentPurge($docId, $purgeDate)` - Secure deletion scheduling
  - `getRetentionReport()` - Compliance reporting
- **Automation:** Cron jobs for expiry checking and archival
- **Implementation Phase:** Phase 1 (Week 3)

### B-8: KaizenTasks Integration
**Requirement:** Keeps workflow moving without email chaos - KaizenTasks hook for reviews & approvals (DOC_REVIEW, DOC_APPROVED)

**Implementation Mapping:**
- **Integration Points:** 
  - Document lifecycle transitions → KaizenTasks creation
  - Task completion → DMS status updates
- **Functions:**
  - `createKaizenTask($event, $docId, $assignedTo, $priority)` - Task creation
  - `createReviewTask($docId, $reviewers)` - Review workflow
  - `createApprovalTask($docId, $approvers)` - Approval workflow
  - `handleTaskCallback($taskId, $result)` - Task completion handling
- **Task Types Created:**
  - `DOC_REVIEW` - Document review requests
  - `DOC_APPROVED` - Post-approval actions
  - `DOC_TRAINING` - Training assignments
  - `PSO_REVIEW` - Safety reviews
- **Task Payload:** `{doc_id, rev, site, area, SC_flag, url, tags[]}`
- **Implementation Phase:** Phase 1 (Week 3)

---

## Efficiency & Effectiveness Requirements (E-1 to E-10)

### E-1: Read-&-Understood Micro-Training
**Requirement:** Fast, line-side competence proof - Read-&-Understood micro-training + pass-threshold gate

**Implementation Mapping:**
- **Primary Tables:**
  - `dms_training_records` - Training completion tracking
  - `dms_training_requirements` - Role/department requirements
  - `dms_quiz_questions` - Assessment questions
- **Functions:**
  - `assignTraining($docId, $users, $trainingType)` - Training assignment
  - `startTraining($trainingId)` - Training initiation
  - `recordReadConfirmation($docId, $userId, $timeSpent)` - Read confirmation
  - `createQuiz($docId, $questions)` - Quiz creation
  - `evaluateQuizResponse($trainingId, $answers)` - Assessment evaluation
  - `generateCertificate($trainingId)` - Certificate generation
  - `checkTrainingCompliance($docId)` - Compliance monitoring
- **Features:** Minimum read time validation, pass/fail thresholds, certificate generation
- **Implementation Phase:** Phase 3 (Week 4)

### E-2: Safety Gate (PSO) Auto-enforced
**Requirement:** Blocks unsafe release - Safety Gate (PSO) auto-enforced for Ⓢ changes (belts/anchorages/welds, etc.)

**Implementation Mapping:**
- **Primary Tables:**
  - `master_psa_rules` - Safety characteristic detection rules
  - `master_safety_characteristics` - Safety characteristic definitions
  - `dms_documents.pso_required`, `pso_approved_by` - PSO approval tracking
- **Functions:**
  - `detectSafetyCharacteristics($docId, $content)` - Auto-detection of safety content
  - `requirePSOApproval($docId, $reason)` - PSO approval requirement
  - `validatePSOApproval($docId, $psoUserId, $approvalData)` - PSO approval validation
  - `blockReleaseWithoutPSO($docId)` - Release blocking mechanism
  - `getPSOPendingDocs()` - PSO queue management
- **Automation:** Regex-based detection, automatic blocking, escalation chains
- **Implementation Phase:** Phase 3 (Week 4)

### E-3: Linked-docs Integrity
**Requirement:** Stops "orphan" edits - Linked-docs integrity (PFMEA ↔ CP ↔ WI sync check)

**Implementation Mapping:**
- **Primary Tables:**
  - `dms_document_links` - Document relationships
  - `dms_process_links` - PFMEA↔CP↔WI specific links
- **Functions:**
  - `linkDocuments($sourceId, $targetId, $linkType, $linkStrength)` - Link creation
  - `validatePFMEAtoCPLink($pfmeaId, $cpId)` - PFMEA-CP validation
  - `validateCPtoWILink($cpId, $wiId)` - CP-WI validation
  - `checkOrphanLinks($docId)` - Orphan detection
  - `syncLinkedDocuments($docId)` - Synchronization validation
  - `generateLinkageReport($docId)` - Impact analysis
- **Validation:** Cross-document characteristic checking, sync status tracking
- **Implementation Phase:** Phase 3 (Week 4)

### E-4: Effective-date Scheduler & Readiness Check
**Requirement:** Zero-downtime changeover - Effective-date scheduler & readiness check (training %, PSO, links)

**Implementation Mapping:**
- **Primary Tables:**
  - `dms_readiness_checks` - Readiness validation tracking
  - `dms_documents.effective_date` - Scheduled effective dates
- **Functions:**
  - `scheduleEffectiveDate($docId, $effectiveDate)` - Future date scheduling
  - `checkReadinessForRelease($docId)` - Comprehensive readiness validation
  - `validateTrainingCompletion($docId)` - Training readiness
  - `validatePSOApproval($docId)` - PSO readiness
  - `validateLinkedDocsSync($docId)` - Link readiness
  - `blockOrReleaseDocument($docId)` - Go/no-go decision
  - `scheduleReadinessChecks($docId, $effectiveDate)` - Automated checking
- **Checks:** Training completion %, PSO approval, linked doc sync, controlled copies
- **Implementation Phase:** Phase 4 (Week 5)

### E-5: Shop-floor Kiosk/Handheld Access
**Requirement:** Eliminates outdated instructions in production - Shop-floor kiosk/handheld access showing only active revision; banners on block

**Implementation Mapping:**
- **Primary Tables:**
  - `dms_kiosk_access_log` - Kiosk access tracking
  - `dms_access_log` - General access logging
- **Functions:**
  - `KioskAccess::getActiveDocument()` - Active revision only
  - Kiosk-specific UI showing document status banners
  - Access restrictions based on document status
- **Features:** Only effective documents visible, blocking banners, access logging
- **Implementation Phase:** Phase 4 (Week 5)

### E-6: Controlled-copy Ledger
**Requirement:** Physical-print governance - Controlled-copy ledger with auto-expiry & retrieval tasks

**Implementation Mapping:**
- **Primary Table:** `dms_controlled_copies` - Complete copy lifecycle
- **Functions:**
  - `ControlledCopy::trackCopies()` - Copy management
  - Auto-expiry based on issue date + retention period
  - Auto-generation of `COPY_RETRIEVE` KaizenTasks for expired copies
- **Features:** Copy numbering, expiry dates, retrieval task automation
- **Implementation Phase:** Phase 4 (Week 6)

### E-7: Periodic Review Cycle
**Requirement:** Prevents document rot - Periodic review cycle with KaizenTasks reminders

**Implementation Mapping:**
- **Primary Tables:**
  - `dms_review_schedule` - Review scheduling
  - `master_review_cycles` - Review frequency definitions
- **Functions:**
  - `ReviewManager::schedulePeriodicReview()` - Review scheduling
  - Auto-generation of `DOC_REVIEW_DUE` tasks at T-30, T-7, T-0
- **Implementation Phase:** Phase 4 (Week 6)

### E-8: Dashboards & KPIs
**Requirement:** Supports Management Review & continual improvement - Dashboards & KPIs (Read-&-Understood %, docs overdue, approval lead time, SC sign-off %)

**Implementation Mapping:**
- **Functions:**
  - `Dashboard::getTrainingCompletionRate()` - Training metrics
  - `Dashboard::getApprovalLeadTime()` - Workflow metrics
  - `Dashboard::getOverdueDocuments()` - Overdue tracking
  - Management dashboard with ISO compliance KPIs
- **Implementation Phase:** Phase 4 (Week 6)

### E-9: Bulk Import/Legacy Migration
**Requirement:** Smooth go-live & audits of past work - Bulk import / legacy migration utility with metadata mapping

**Implementation Mapping:**
- **Functions:**
  - `Migration::importLegacyDocuments()` - Bulk import
  - Excel template with metadata mapping
  - File integrity validation during import
- **Implementation Phase:** Phase 4 (Week 6)

### E-10: Where-used/Impact Analysis
**Requirement:** Faster, safer revisions - Where-used / impact analysis graph before change

**Implementation Mapping:**
- **Functions:**
  - `ImpactAnalysis::getDocumentDependencies()` - Dependency mapping
  - Graph visualization of affected documents
  - Based on `dms_document_links` relationship data
- **Implementation Phase:** Phase 4 (Week 6)

---

## Good-to-Have Requirements (G-1 to G-7) - Future Enhancements

### G-1: AI Metadata Pre-fill & Content QA
**Requirement:** Speeds drafting, reduces errors - AI metadata pre-fill & content QA (flag missing torque spec, SC tag suggestion)

**Implementation Mapping:**
- **Functions:**
  - `AI::suggestMetadata()` - AI-powered metadata suggestions
  - `AI::validateContent()` - Content quality analysis
- **Implementation Phase:** Future (Phase 5+)

### G-2: Auto-translation Sync
**Requirement:** Multilingual workforce readiness - Auto-translation sync (Marathi ↔ English paired revs)

**Implementation Mapping:**
- **Table:** `dms_documents.parent_doc_id` - Language version linking
- **Functions:**
  - `Translation::syncRevisions()` - Translation synchronization
- **Implementation Phase:** Future (Phase 5+)

### G-3: 3-way CAD Diff & Markup Viewer
**Requirement:** Clear visual changes for Design & QA - 3-way CAD diff & markup viewer for drawings

**Implementation Mapping:**
- **Functions:**
  - `CADViewer::compareRevisions()` - CAD comparison
- **Implementation Phase:** Future (Phase 5+)

### G-4: Mobile Offline Mode
**Requirement:** Resilience for network drops - Mobile offline mode with automatic sync & conflict resolution

**Implementation Mapping:**
- **Functions:**
  - `OfflineSync::downloadForOffline()` - Local storage management
- **Implementation Phase:** Future (Phase 5+)

### G-5: REST/GraphQL API
**Requirement:** Seamless external collaboration - REST/GraphQL API for customers & suppliers (CSR subsets)

**Implementation Mapping:**
- **Endpoints:** `/api/v1/documents`, `/graphql`
- **Implementation Phase:** Future (Phase 5+)

### G-6: Digital Signature Integration
**Requirement:** Formal compliance with Indian IT Act - Digital signature integration (DSC/USB token) for legal documents

**Implementation Mapping:**
- **Functions:**
  - `DigitalSignature::signDocument()` - DSC integration
- **Implementation Phase:** Future (Phase 5+)

### G-7: Auto-classification of Safety Characteristics
**Requirement:** Reduces manual PSO gate tagging errors - Auto-classification of safety vs non-safety characteristics via ML

**Implementation Mapping:**
- **Functions:**
  - `AI::classifySafetyCharacteristics()` - ML-based classification
- **Implementation Phase:** Future (Phase 5+)

---

## DMS ↔ KaizenTasks Event Mapping

| DMS Event | Requirement | KaizenTask Type | Implementation Function |
|-----------|-------------|----------------|------------------------|
| Draft submitted for review | B-3, B-8 | `DOC_REVIEW` | `createReviewTask()` |
| Document approved | B-3, B-8 | `DOC_APPROVED` | `createApprovalTask()` |
| Effective-date readiness blocked | E-4 | `DOC_BLOCKED` | `createKaizenTask('DOC_BLOCKED')` |
| Read-&-Understood below threshold | E-1, E-4 | `STOP_RELEASE` | `createKaizenTask('STOP_RELEASE')` |
| Periodic review due | E-7 | `DOC_REVIEW_DUE` | Auto-scheduled task |
| Controlled copy expires/lost | E-6 | `COPY_RETRIEVE` | Auto-generated from expiry |
| Audit finding links to doc | Compliance | `DOC_CAPA` | External trigger |

---

## Master Data Requirements Mapping

| Master Table | Requirement Source | Owner | Implementation Status |
|--------------|-------------------|-------|---------------------|
| `master_doc_types` | B-1, B-2 | QA Doc Control | Phase 0 |
| `master_process_areas` | B-1, B-2 | QA/Manufacturing Eng | Phase 0 |
| `master_sites` | B-2, Context | Management Rep | Phase 0 |
| `master_lines` | E-5, Context | Production Planning | Phase 0 |
| `master_shifts` | Context | HR/Production | Phase 0 |
| `master_models` | Context | Design/PPC | Phase 0 |
| `master_customers` | Context | Sales/QA | Phase 0 |
| `master_languages` | G-2 | QA | Phase 0 |
| `master_retention_classes` | B-7 | QA Doc Control | Phase 0 |
| `master_safety_characteristics` | E-2 | PSO | Phase 0 |
| `master_review_cycles` | E-7 | QA | Phase 0 |
| `master_notification_channels` | B-8, E-7 | IT/Management Rep | Phase 0 |
| `master_psa_rules` | E-2 | PSO | Phase 0 |

---

## Implementation Priority Summary

**Phase 0 (Week 1): Database Foundation**
- All master tables creation - **CRITICAL**
- Core DMS tables creation
- Data relationships and constraints

**Phase 1 (Week 2-3): Basic Compliance (B-1 to B-8)**
- Single repository, metadata, lifecycle management
- Search, audit trail, retention, KaizenTasks integration

**Phase 2 (Week 4-5): Efficiency Features (E-1 to E-5)**
- Training system, safety gates, document linking
- Effective date scheduling, kiosk access

**Phase 3 (Week 6): Advanced Efficiency (E-6 to E-10)**
- Controlled copies, reviews, dashboards, migration, impact analysis

**Phase 4 (Future): Good-to-Have (G-1 to G-7)**
- AI features, mobile offline, APIs, digital signatures

This mapping ensures every requirement from the summary document has a corresponding implementation action, database table, and function specification in the implementation plan.
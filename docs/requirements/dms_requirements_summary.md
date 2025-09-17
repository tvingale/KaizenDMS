# Document Management System (DMS) — Requirements Summary

> **Scope (verbatim):** “Design, manufacture and supply of bus seats and pressed/fabricated sheet‑metal components.”
> **Sites:** Main – Plot B‑75, MIDC, Ahmednagar 414111, MH, India.  Unit‑1 – G‑44, MIDC, Ahmednagar 414111, MH, India.

---

## 1. Requirement Tiers

### 1.1 Basic (Minimum Viable Compliance)
| # | Capability | Why it’s Basic |
|---|------------|----------------|
| B‑1 | **Single repository** for all controlled docs (Policy, SOP, WI, PFMEA, CP, Drawings, Forms). | Core clause 7.5 (ISO 9001/IATF 16949). |
| B‑2 | **Doc metadata & auto‑numbering** (title, type, area, rev, owner, status). | Traceability; audit readiness. |
| B‑3 | **Draft → Review → Approved → Effective → Obsolete** lifecycle with e‑sign. | Ensures only validated info reaches shop floor. |
| B‑4 | **Controlled PDF output** with watermark & QR/Barcode. | Prevents misuse of uncontrolled prints. |
| B‑5 | **Search & filters** by id/title/area/type/rev/status. | Users must find the latest doc fast. |
| B‑6 | **Immutable audit trail** (who/what/when). | Mandatory for third‑party audits. |
| B‑7 | **Retention rules** (≥3 yrs, ≥10 yrs Safety/PPAP). | Meets legal & IATF retention. |
| B‑8 | **KaizenTasks hook for reviews & approvals** (`DOC_REVIEW`, `DOC_APPROVED`). | Keeps workflow moving without e‑mail chaos. |

### 1.2 Required for Efficiency & Effectiveness
| # | Capability | Benefit |
|---|------------|---------|
| E‑1A | **Simple Read‑&‑Understood acknowledgment** with digital signature capture. | Fast compliance with audit trail (Phase 1). |
| E‑1B | **Quiz‑based competency training** + pass‑threshold gate. | Proven competence validation (Phase 2). |
| E‑2 | **Safety Gate (PSO)** auto‑enforced for Ⓢ changes (belts/anchorages/welds, etc.). | Blocks unsafe release. |
| E‑3 | **Linked‑docs integrity** (PFMEA ↔ CP ↔ WI sync check). | Stops “orphan” edits. |
| E‑4 | **Effective‑date scheduler & readiness check** (training %, PSO, links). | Zero‑downtime changeover. |
| E‑5 | **Shop‑floor kiosk/handheld access** showing only active revision; banners on block. | Eliminates outdated instructions in production. |
| E‑6 | **Controlled‑copy ledger** with auto‑expiry & retrieval tasks. | Physical‑print governance. |
| E‑7 | **Periodic review cycle with KaizenTasks reminders**. | Prevents document rot. |
| E‑8 | **Dashboards & KPIs** (Read‑&‑Understood %, docs overdue, approval lead time, SC sign‑off %). | Supports Management Review & continual improvement. |
| E‑9 | **Bulk import / legacy migration utility** with metadata mapping. | Smooth go‑live & audits of past work. |
| E‑10 | **Where‑used / impact analysis** graph before change. | Faster, safer revisions. |

### 1.3 Good to Have (Future Enhancements)
| # | Capability | Value Add |
|---|------------|-----------|
| G‑1 | **AI metadata pre‑fill & content QA** (flag missing torque spec, SC tag suggestion). | Speeds drafting, reduces errors. |
| G‑2 | **Auto‑translation sync** (Marathi ↔ English paired revs). | Multilingual workforce readiness. |
| G‑3 | **3‑way CAD diff & markup viewer** for drawings. | Clear visual changes for Design & QA. |
| G‑4 | **Mobile offline mode** with automatic sync & conflict resolution. | Resilience for network drops. |
| G‑5 | **REST/GraphQL API** for customers & suppliers (CSR subsets). | Seamless external collaboration. |
| G‑6 | **Digital signature integration** (DSC/USB token) for legal documents. | Formal compliance with Indian IT Act. |
| G‑7 | **Auto‑classification of safety vs non‑safety characteristics via ML.** | Reduces manual PSO gate tagging errors. |

---

## 2. DMS ↔ KaizenTasks Interaction Map
| Event in DMS | Task in KaizenTasks | Default Priority | Escalation Chain |
|--------------|--------------------|------------------|------------------|
| Draft **submitted for review** | `DOC_REVIEW` (owner → reviewers) | Medium | Reviewer > QA Head |
| Doc **approved** | `DOC_APPROVED` (training & controlled‑copy sub‑tasks spawn) | High | Line Lead > QA Manager |
| **Effective‑date readiness blocked** | `DOC_BLOCKED` (reason tag) | Urgent | QA Manager > PSO |
| **Read‑&‑Understood** below threshold at T‑0 | `STOP_RELEASE` | Critical | Line Lead > PSO |
| **Periodic review due** (T‑30, T‑7, T‑0) | `DOC_REVIEW_DUE` | Medium | Owner > QA Manager |
| Controlled copy **expires / lost** | `COPY_RETRIEVE` | Medium | Area Owner > QA Doc Control |
| Audit finding **links to doc** | `DOC_CAPA` | High | Owner > MR |

Task payload must include: `doc_id`, `rev`, `site`, `area`, `SC_flag`, deep‑link URL, and `tags[]` (e.g., `["DMS","SC","PFMEA"]`).

---

## 3. Master Data Tables (Dedicated to DMS)
| Table | Key Fields (sample) | Owner |
|-------|--------------------|-------|
| **`master_doc_types`** | `doc_type_id`, `name`, `template_path` | QA Doc Control |
| **`master_process_areas`** | `area_id`, `name` (Welding, Stitching, …) | QA / Manufacturing Engg. |
| **`master_sites`** | `site_id`, `code`, `address` | MR |
| **`master_lines`** | `line_id`, `site_id`, `name` | Production Planning |
| **`master_shifts`** | `shift_id`, `code`, `start_time`, `end_time` | HR/Production |
| **`master_models`** | `model_id`, `program`, `name` | Design / PPC |
| **`master_customers`** | `cust_id`, `name`, `csr_flags` | Sales / QA |
| **`master_languages`** | `lang_id`, `name`, `rtl_flag` | QA |
| **`master_retention_classes`** | `ret_id`, `name`, `years_to_keep` | QA Doc Control |
| **`master_safety_characteristics`** | `sc_id`, `description`, `needs_pso` (Y/N) | PSO |
| **`master_review_cycles`** | `cycle_id`, `months` (12, 24) | QA |
| **`master_notification_channels`** | `channel_id`, `type` (Email, WhatsApp) | IT / MR |
| **`master_psa_rules`** | `rule_id`, `trigger_area` (belts, welds…), `pso_required` | PSO |

*(DMS consumes additional shared masters like `master_roles` and authentication via KaizenAuth but the above are DMS‑specific.)*

---

### 4. File Naming Convention
```
DMS/YYYY-MM/DD_[doc_type]_[doc_id]_[title-slug]_[rev].pdf
```
Ensures deterministic, audit‑friendly storage paths.

---

### 5. Initial Roll‑out Recommendation
1. **Pilot scope:** Slim & GSRTC programs; Welding & Assembly areas only.  
2. Load masters, migrate legacy docs, verify QR access on kiosks.  
3. Enable Read‑&‑Understood gate; monitor KPIs for two cycles before full plant go‑live.

---

*Revision 0 • Generated 2025‑09‑11*


*Revision 1 • Updated 2025‑09‑12 • Added E-1A/E-1B split for phased training implementation*

**Note:** Detailed live environment implementation guide available in `dms_detailed_implementation_guide.md`

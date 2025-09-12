# Quick Sharing Guide
## What to Tell Other Modules & What Files to Share

### 🚀 Simple 3-Step Process

#### Step 1: Share the Package
**Send:** The entire `master-data-integration` folder (as ZIP attachment)
**Message:** "Here's our master data integration framework - start with README.md"

#### Step 2: Key Points to Communicate
**Tell them:**
- "Don't create your own lookup tables - reuse our 5 existing master types first"
- "If you need new data, use our request format so other modules can benefit too"
- "Direct database access is recommended for best performance"
- "Timeline: 1 week for analysis, 1 week for implementation"

#### Step 3: Set Expectations
**Timeline:** 2 weeks total
**Contact:** dev@kaizen.com for technical questions
**Next Step:** Completed request format within 1 week

---

### 📁 Essential Files to Share

**Priority 1 - Must Share:**
1. `README.md` - Start here overview
2. `EXISTING_MASTER_DATA_CATALOG.md` - What exists now
3. `templates/master_data_request_template.json` - Blank form

**Priority 2 - Very Helpful:**
4. `MASTER_DATA_REQUEST_FORMAT.md` - Detailed format explanation
5. `examples/task_module_example_request.json` - Real example

**Priority 3 - For Developers:**
6. `MASTER_DATA_INTEGRATION_GUIDELINES.md` - Technical details
7. `examples/integration_code_samples.php` - Code samples

---

### 💬 Simple Email Script

**Subject:** Master Data Integration - Avoid Creating Duplicate Tables

Hi [Team],

We've created a framework to share master data across KaizenFlow modules. Instead of creating your own sites/departments/customers tables, you can reuse ours.

**Attached:** Complete integration documentation
**Start with:** README.md file
**Goal:** Avoid duplicate data, maintain consistency

**Next step:** Review existing masters and send us your requirements using our standard format.

Questions? Reply to this email.

Thanks,
[Your name]

---

### ✅ Pre-Send Checklist

Before sharing with any module:
- [ ] Zip the entire `master-data-integration` folder
- [ ] Test that all documentation links work
- [ ] Update any placeholder dates/emails
- [ ] Confirm your contact information is correct
- [ ] Set reminder to follow up in 1 week

---

### 🎯 What Success Looks Like

**Module team response:** "We reviewed the catalog and can reuse 80% of existing data. Here's our request for the remaining 20%."

**What you want to avoid:** "We already built our own tables before seeing this."

**Key message:** "Check what exists first, then request what's missing."
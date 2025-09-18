# OnlyGod Unit Ledger Extension - Deployment Guide

## 🚀 **Deployment Checklist**

### Pre-Deployment
- [ ] Backup CiviCRM database
- [ ] Test extension in development environment
- [ ] Verify CiviRules extension is installed
- [ ] Review activity types that will be mapped to programs

### Installation Steps

1. **Copy Extension Files**
   ```bash
   cp -r org.onlygod.unitledger /path/to/civicrm/ext/
   ```

2. **Enable Extension**
   - Go to Administer → System Settings → Extensions
   - Find "OnlyGod Unit Ledger" and click "Install"
   - Verify installation completes without errors

3. **Configure Settings**
   - Go to Administer → Unit Ledger Settings
   - Set up program mappings (activity type ID → program name)
   - Set up unit multipliers for each program
   - Save configuration

4. **Set Up CiviRules**
   - Go to Administer → CiviRules → Rules
   - Create rules for activity triggers:
     - **Activity Created**: Add "Post Delta to Unit Ledger" action
     - **Activity Updated**: Add "Post Delta to Unit Ledger" action  
     - **Activity Deleted**: Add "Post Delta to Unit Ledger" action
   - Enable the rules

5. **Test Functionality**
   - Create a test activity with mapped activity type
   - Verify unit ledger entry is created
   - Check case tab shows the entry
   - Test CSV import with sample data

### Post-Deployment

- [ ] Verify unit ledger entries are being created automatically
- [ ] Test case tab functionality
- [ ] Test CSV import functionality
- [ ] Create SearchKit reports for unit ledger data
- [ ] Train staff on new functionality

## 🔧 **Configuration Examples**

### Default Program Mappings
```json
{
  "1": "FCS",
  "2": "FCS", 
  "3": "FCS",
  "4": "WellPoint"
}
```

### Default Unit Multipliers
```json
{
  "FCS": 1.0,
  "WellPoint": 1.0
}
```

## 📊 **Sample CiviRules Configuration**

### Rule 1: Activity Created
- **Trigger**: Activity Created
- **Conditions**: None (or add specific activity types)
- **Actions**: Post Delta to Unit Ledger

### Rule 2: Activity Updated  
- **Trigger**: Activity Updated
- **Conditions**: None (or add specific activity types)
- **Actions**: Post Delta to Unit Ledger

### Rule 3: Activity Deleted
- **Trigger**: Activity Deleted
- **Conditions**: None (or add specific activity types)
- **Actions**: Post Delta to Unit Ledger

## 🧪 **Testing Scenarios**

### 1. Basic Unit Posting
1. Create an activity with mapped activity type
2. Verify unit ledger entry is created
3. Check balance calculation is correct

### 2. Activity Updates
1. Update an existing activity
2. Verify delta is posted correctly
3. Check balance reflects the change

### 3. CSV Import
1. Create sample CSV with unit data
2. Run dry-run import
3. Review preview results
4. Run actual import
5. Verify records are created

### 4. Case Tab
1. Open a case with unit ledger entries
2. Verify tab shows current balances
3. Check ledger entries are displayed correctly
4. Test export functionality

## 🚨 **Troubleshooting**

### Common Issues

**Units not posting automatically**
- Check CiviRules are enabled and configured
- Verify program mappings are set up
- Check activity types are mapped correctly

**Balance calculations incorrect**
- Use API to recompute balances:
  ```php
  civicrm_api4('UnitLedger', 'recomputeBalances', [
    'case_id' => 123,
    'program' => 'FCS'
  ]);
  ```

**CSV import fails**
- Check CSV format matches requirements
- Verify required fields are present
- Check file size (max 10MB)

### Log Files
Check CiviCRM logs for error messages:
- `/var/log/civicrm/civicrm.log`
- CiviCRM System Log (Administer → System Settings → System Log)

## 📈 **Performance Considerations**

- The unit ledger table is append-only, so it will grow over time
- Consider archiving old entries if needed
- Indexes are optimized for common queries (case_id, program, transaction_date)
- Balance calculations are cached in the ledger entries

## 🔄 **Maintenance**

### Regular Tasks
- Monitor unit ledger entries for accuracy
- Review and update program mappings as needed
- Archive old CSV import files
- Check CiviRules are still active

### Backup Considerations
- Unit ledger data is included in standard CiviCRM backups
- Consider separate backup of unit ledger table for critical data
- Test restore procedures in development environment

## 🆘 **Rollback Plan**

If issues arise and rollback is needed:

1. **Disable Extension**
   - Go to Administer → System Settings → Extensions
   - Disable "OnlyGod Unit Ledger"

2. **Disable CiviRules**
   - Go to Administer → CiviRules → Rules
   - Disable all unit ledger related rules

3. **Data Preservation**
   - Unit ledger data remains in database
   - No data loss occurs during rollback
   - Can re-enable extension later

4. **Complete Removal** (if needed)
   - Uninstall extension
   - Manually drop `civicrm_unit_ledger` table
   - Remove custom fields from activities

## 📞 **Support Contacts**

- **Development Team**: [Contact Information]
- **CiviCRM Support**: [Support Channel]
- **Documentation**: See README.md in extension directory

## ✅ **Deployment Sign-off**

- [ ] Extension installed successfully
- [ ] Configuration completed
- [ ] CiviRules set up and tested
- [ ] Basic functionality tested
- [ ] Staff trained on new features
- [ ] Documentation provided to users

**Deployed by**: _________________  
**Date**: _________________  
**Version**: 1.0.0

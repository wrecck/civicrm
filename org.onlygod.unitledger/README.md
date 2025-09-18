# OnlyGod Unit Ledger Extension

An append-only unit ledger system for CiviCRM that integrates with activities to track unit transactions and maintain running balances.

## Features

- **Append-only Unit Ledger**: Tracks unit transactions with running balances
- **Activity Integration**: Automatically posts units when activities are created/updated/deleted
- **Delta-aware Posting**: Only posts the difference when activities are modified
- **CSV Import**: Import unit data with dry-run and duplicate handling
- **Case Integration**: View unit ledger as a case tab
- **SearchKit Reports**: Generate reports by case/program/date range
- **CiviRules Integration**: Automate unit posting based on activity triggers

## Installation

1. Copy the extension to your CiviCRM extensions directory:
   ```
   cp -r org.onlygod.unitledger /path/to/civicrm/ext/
   ```

2. Enable the extension in CiviCRM:
   - Go to Administer → System Settings → Extensions
   - Find "OnlyGod Unit Ledger" and click "Install"

3. Configure the extension:
   - Go to Administer → Unit Ledger Settings
   - Set up program mappings and unit multipliers

## Configuration

### Program Mappings

Map activity types to program names in the settings page:

```json
{
  "1": "FCS",
  "2": "FCS", 
  "3": "FCS",
  "4": "WellPoint"
}
```

### Unit Multipliers

Set multipliers for different programs:

```json
{
  "FCS": 1.0,
  "WellPoint": 1.0
}
```

## Usage

### Automatic Unit Posting

The extension automatically posts units when activities are created, updated, or deleted. This is handled by CiviRules:

1. Go to Administer → CiviRules → Rules
2. Create a new rule for activity triggers
3. Add the "Post Delta to Unit Ledger" action

### Manual Unit Posting

You can manually post units using the API:

```php
$result = civicrm_api4('UnitLedger', 'postDelta', [
  'activity_id' => 123,
]);
```

### CSV Import

1. Go to Administer → Import Unit Ledger Data
2. Upload a CSV file with the required format
3. Choose import options (dry-run, skip duplicates, etc.)
4. Review results and import

#### CSV Format

Required fields:
- `program`: Program name (e.g., FCS, WellPoint)
- `units`: Number of units (numeric)
- `transaction_date`: Date of transaction (YYYY-MM-DD HH:MM:SS)

Optional fields:
- `unit_type`: Type of units (default: hours)
- `transaction_type`: Type of transaction (default: import)
- `description`: Description of the transaction
- `contact_id`: Contact ID
- `case_id`: Case ID

Example CSV:
```csv
program,units,transaction_date,unit_type,description,contact_id,case_id
FCS,2.5,2025-01-27 10:00:00,hours,FCS Authorization,123,456
WellPoint,1.0,2025-01-27 11:00:00,hours,WellPoint Authorization,123,456
```

### Viewing Unit Ledger

- **Case Tab**: View unit ledger entries for a specific case
- **SearchKit Reports**: Create custom reports by case/program/date range
- **API Access**: Use the UnitLedger APIv4 for programmatic access

## API Reference

### UnitLedger APIv4

#### Create
```php
$result = civicrm_api4('UnitLedger', 'create', [
  'values' => [
    'activity_id' => 123,
    'case_id' => 456,
    'program' => 'FCS',
    'units' => 2.5,
    'transaction_date' => '2025-01-27 10:00:00',
  ],
]);
```

#### Get
```php
$result = civicrm_api4('UnitLedger', 'get', [
  'where' => [
    ['case_id', '=', 456],
    ['program', '=', 'FCS'],
  ],
  'orderBy' => ['transaction_date' => 'DESC'],
]);
```

#### Recompute Balances
```php
$result = civicrm_api4('UnitLedger', 'recomputeBalances', [
  'case_id' => 456,
  'program' => 'FCS',
  'start_date' => '2025-01-01',
  'end_date' => '2025-01-31',
]);
```

#### Get Balance
```php
$result = civicrm_api4('UnitLedger', 'getBalance', [
  'case_id' => 456,
  'program' => 'FCS',
  'as_of_date' => '2025-01-27',
]);
```

## Troubleshooting

### Common Issues

1. **Units not posting automatically**
   - Check CiviRules configuration
   - Verify program mappings are set up correctly
   - Check activity types are mapped to programs

2. **Balance calculations incorrect**
   - Use the recompute balances function
   - Check for duplicate entries
   - Verify transaction dates are correct

3. **CSV import fails**
   - Check CSV format matches requirements
   - Verify required fields are present
   - Check file size (max 10MB)

### Rollback Steps

If you need to rollback the extension:

1. Disable the extension in CiviCRM
2. Uninstall the extension
3. The database table will be preserved (append-only design)
4. To completely remove, manually drop the `civicrm_unit_ledger` table

## Development

### File Structure

```
org.onlygod.unitledger/
├── info.xml                          # Extension metadata
├── org.onlygod.unitledger.php        # Main extension file
├── sql/
│   └── auto_install.sql              # Database schema
├── CRM/
│   └── UnitLedger/
│       ├── API/v4/UnitLedger.php     # APIv4 implementation
│       ├── BAO/UnitLedger.php        # Business logic
│       ├── DAO/UnitLedger.php        # Data access
│       ├── Form/Import.php           # CSV import form
│       ├── Page/CaseTab.php          # Case tab page
│       ├── CiviRules/Actions/        # CiviRules integration
│       └── Config/Route.php          # Route configuration
└── templates/                        # Smarty templates
```

### Adding New Features

1. Create new classes in the appropriate directory
2. Register routes in `Config/Route.php`
3. Add API endpoints in `API/v4/UnitLedger.php`
4. Update templates as needed

## Support

For support and questions:
- Check the CiviCRM logs for error messages
- Review the extension settings
- Contact the development team

## License

This extension is licensed under the AGPL-3.0 license.

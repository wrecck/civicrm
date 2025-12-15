{* CSV Upload Page - FCS Authorization Upload *}
<div class="crm-container">
  <div class="crm-section">
    <h3>{ts}FCS Authorization Upload{/ts}</h3>
    
    {if $error}
      <div class="crm-container">
        <div class="crm-section">
          <h2>Error</h2>
          <p>Error: {$error}</p>
        </div>
      </div>
    {else}
      {if $formData.submitted|default:false}
        {if $formData.success|default:false}
          <div class="messages status">
            <p>{$formData.message|default:'CSV uploaded successfully!'}</p>
            {if $formData.results|default:false}
              <ul>
                <li>{ts}Successfully processed:{/ts} {$formData.results.success}</li>
                <li>{ts}Cases created:{/ts} {$formData.results.created}</li>
                <li>{ts}Cases updated:{/ts} {$formData.results.updated}</li>
                <li>{ts}Skipped:{/ts} {$formData.results.skipped}</li>
              </ul>
            {/if}
          </div>
        {else}
          <div class="messages error">
            <p>{$formData.error|default:'An error occurred during upload.'}</p>
          </div>
        {/if}
        
        {if $formData.errors|default:false && count($formData.errors) > 0}
          <div class="messages warning">
            <h4>{ts}Row Errors:{/ts}</h4>
            <ul>
              {foreach from=$formData.errors item=error}
                <li>{$error}</li>
              {/foreach}
            </ul>
          </div>
        {/if}
      {/if}
      
      <div class="crm-form-block">
        <form action="{$uploadUrl}" method="post" enctype="multipart/form-data" id="csv-upload-form">
          <div class="crm-section">
            <div class="label">
              <label for="csv_file">{ts}Select CSV File{/ts} <span class="crm-marker">*</span></label>
            </div>
            <div class="content">
              <input type="file" name="csv_file" id="csv_file" accept=".csv" required="required" />
              <div class="description">
                {ts}Please select a CSV file containing FCS authorization data.{/ts}
              </div>
            </div>
          </div>
          
          <div class="crm-section">
            <div class="label">
              <label for="description">{ts}Description (Optional){/ts}</label>
            </div>
            <div class="content">
              <textarea name="description" id="description" rows="3" cols="50" placeholder="{ts}Enter a description for this upload...{/ts}"></textarea>
            </div>
          </div>
          
          <div class="crm-submit-buttons">
            <input type="submit" name="upload_csv" value="{ts}Upload CSV{/ts}" class="crm-form-submit default" />
            <input type="button" name="cancel" value="{ts}Cancel{/ts}" class="crm-form-submit" onclick="history.back();" />
          </div>
        </form>
      </div>
      
      <div class="crm-section">
        <h4>{ts}Instructions{/ts}</h4>
        <div class="help">
          <p>{ts}Please ensure your CSV file contains the following columns:{/ts}</p>
          <ul>
            <li>{ts}Assessment ID{/ts}</li>
            <li>{ts}Reauth (R1, R2){/ts}</li>
            <li>{ts}Service Type{/ts}</li>
            <li>{ts}Referring Agency Name{/ts}</li>
            <li>{ts}Client First Name{/ts}</li>
            <li>{ts}Client Last Name{/ts}</li>
            <li>{ts}DOB{/ts}</li>
            <li>{ts}ProviderOne Number{/ts}</li>
            <li>{ts}Client Mailing Address{/ts}</li>
            <li>{ts}City{/ts}</li>
            <li>{ts}State{/ts}</li>
            <li>{ts}Client Contact Number{/ts}</li>
            <li>{ts}Medicaid Eligibility Determination{/ts}</li>
            <li>{ts}Health Needs-Based Criteria{/ts}</li>
            <li>{ts}Risk Factors{/ts}</li>
            <li>{ts}Assigned Provider Name{/ts}</li>
            <li>{ts}Enrollment Status{/ts}</li>
            <li>{ts}Notes{/ts}</li>
            <li>{ts}Benefit Limitation (180 Day Period){/ts}</li>
            <li>{ts}Auth Start Date{/ts}</li>
            <li>{ts}Auth End Date{/ts}</li>
          </ul>
          <p><strong>{ts}Note:{/ts}</strong> {ts}The CSV file will create or update cases with the FCS Housing Case Profile data.{/ts}</p>
        </div>
      </div>
    {/if}
  </div>
</div>

{* Add some basic styling *}
<style>
.crm-form-block {
  background: #f9f9f9;
  border: 1px solid #ddd;
  padding: 20px;
  margin: 20px 0;
  border-radius: 4px;
}

.crm-section {
  margin-bottom: 15px;
}

.crm-section .label {
  font-weight: bold;
  margin-bottom: 5px;
}

.crm-section .content input[type="file"] {
  width: 100%;
  max-width: 400px;
}

.crm-section .content textarea {
  width: 100%;
  max-width: 500px;
}

.crm-submit-buttons {
  margin-top: 20px;
  padding-top: 15px;
  border-top: 1px solid #ddd;
}

.help {
  background: #f0f8ff;
  border: 1px solid #b0d4f1;
  padding: 15px;
  border-radius: 4px;
  margin-top: 15px;
}

.help ul {
  margin: 10px 0;
  padding-left: 20px;
}

.help li {
  margin-bottom: 5px;
}
</style>

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
        <div class="messages status">
          <p>{$formData.message|default:'Form submitted successfully!'}</p>
        </div>
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
            <li>{ts}Contact ID or Email{/ts}</li>
            <li>{ts}Case ID{/ts}</li>
            <li>{ts}Activity Type{/ts}</li>
            <li>{ts}Units Allocated{/ts}</li>
            <li>{ts}Date{/ts}</li>
          </ul>
          <p><strong>{ts}Note:{/ts}</strong> {ts}Upload functionality is not yet implemented. This is a form preview only.{/ts}</p>
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

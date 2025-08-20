<?php
  /* @version 0.1.0 */
  echo CHtml::form($form['action'],'post',['enctype' => 'multipart/form-data']);
?>
  <h3 class="clearfix"><?php echo $lang['Quick statistics settings']; ?>
    <div class='pull-right'>
    <?php
      if(Permission::model()->hasSurveyPermission($surveyId, 'surveysettings', 'update')) {
        echo CHtml::htmlButton('<i class="fa fa-check" aria-hidden="true"></i> '.gT('Save'),array('type'=>'submit','name'=>'save'.$pluginClass,'value'=>'save','class'=>'btn btn-primary'));
        echo " ";
        //echo CHtml::htmlButton('<i class="fa fa-check-circle-o " aria-hidden="true"></i> '.gT('Save and close'),array('type'=>'submit','name'=>'save'.$pluginClass,'value'=>'redirect','class'=>'btn btn-default'));
        //echo " ";
        echo CHtml::link(gT('Close'),$form['close'],array('class'=>'btn btn-danger'));
      } else {
        echo CHtml::link(gT('Close'),$form['close'],array('class'=>'btn btn-default'));
      }
    ?>
    </div>
  </h3>

  <div>
      <?php foreach ($aSettings as $legend => $settings) {
          $this->widget('ext.SettingsWidget.SettingsWidget', array(
          //'id'=>'summary',
          'title' => $legend,
          'prefix' => $pluginClass, //This break the label (id!=name)
          'form' => false,
          'formHtmlOptions' => array(
              'class' => 'form-core',
          ),
          'labelWidth' => 6,
          'controlWidth' => 6,
          'settings' => $settings,
          ));
      } ?>
      <div class="mb-3 row setting setting-file " data-name="quotaUploadAndReport[fileUpload]">
		  <label class="default col-form-label text-end col-md-6" for="quotaUploadAndReport_fileUpload">Upload Quota CSV</label>
		  <div class="default col-md-6 controls">
			  <input size="50" class="form-control" accept="text/csv" type="file" name="quotaUploadAndReport[fileUpload]" id="quotaUploadAndReport_fileUpload">
			  <div class="help-block">The CSV file must be formatted in a particular way. Here are the details:
				<ul>
					<li>The filename is used as a unique identifier. If you have already uploaded a quota file with the same name, you will receive an error</li>
					<li>Must contain a header row</li>
					<li>The column header must match the code for the question to create a quota for</li>
					<li>If multiple columns are entered, then multi-level quotas will be generated</li>
					<li>If a column is called "quota" this will be used as the total completes for that quota. If the value is blank or the column doesn't exist, the quota will be created but set as disabled. Note setting to 0 will mean anyone who chooses the matching response will be quota-ed out</li>
					<li>If a column is called "message", and it isn't blank, this will be used as the message to display to the respondent when the quota is reached</li>
					<li>If a column is called "url", and it isn't blank this will be used as the URL to automatically send respondents to when the quota is reached (overrides message)</li>
				</ul>
			</div>
		  </div>
	  </div>
  </div>
  <div class='row'>
    <div class='text-center submit-buttons'>
      <?php
        if(Permission::model()->hasSurveyPermission($surveyId, 'surveysettings', 'update')) {
          echo CHtml::htmlButton('<i class="fa fa-check" aria-hidden="true"></i> '.gT('Save'),array('type'=>'submit','name'=>'save'.$pluginClass,'value'=>'save','class'=>'btn btn-primary'));
          echo " ";
          //echo CHtml::htmlButton('<i class="fa fa-check-circle-o " aria-hidden="true"></i> '.gT('Save and close'),array('type'=>'submit','name'=>'save'.$pluginClass,'value'=>'redirect','class'=>'btn btn-default'));
          //echo " ";
          echo CHtml::link(gT('Close'),$form['close'],array('class'=>'btn btn-danger'));
        } else {
          echo CHtml::link(gT('Close'),$form['close'],array('class'=>'btn btn-default'));
        }
      ?>
    </div>
  </div>
</form>

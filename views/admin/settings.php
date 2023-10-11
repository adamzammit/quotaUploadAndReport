<?php
  /* @version 0.1.0 */
  echo CHtml::form($form['action']);
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
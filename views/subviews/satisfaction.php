<h1><?php echo $titre; ?></h1>
<ul class="nav nav-tabs">
  <li role="presentation"><?php echo CHtml::link($translate->gT("Participation"),array("plugins/direct","plugin"=>"adminStats","function"=>"participation","sid"=>$oSurvey->sid)); ?></li>
  <?php if($showSatisfaction) { ?>
  <li role="presentation" class="active"><a href="#"><?php echo $translate->gT("Satisfaction") ?></a></li>
  <?php } ?>
  <?php if($showAdminSurvey) { ?>
  <li role="presentation"><?php echo CHtml::link($translate->gT("Administration"),array("admin/survey","sa"=>"editsurveysettings","surveyid"=>$oSurvey->sid,'#'=>'pluginsettings')); ?></a></li>
  <?php } ?>
</ul>
<?php
    if(!empty($htmlComment))
    {
         echo CHtml::tag("div",array("class"=>'well clearfix'),$htmlComment);
    }
?>
<?php /* inverse data */
  $aReorederSatisfactions=array();
  foreach($aResponses as $repKey=>$aResponse){
    foreach($aResponse['aSatisfactions'] as $iSatId=>$aSatisfaction)
    {
      if(empty($aReorederSatisfactions[$iSatId]))
      {
        $aReorederSatisfactions[$iSatId]=array(
          'title'=>$aSatisfaction['title'],
          'aResponses'=>array()
        );
      }
      $aReorederSatisfactions[$iSatId]['aResponses'][$repKey]=$aResponses[$repKey]['aSatisfactions'][$iSatId];
      $aReorederSatisfactions[$iSatId]['aResponses'][$repKey]['title']=$aResponses[$repKey]['title'];
      $aReorederSatisfactions[$iSatId]['aResponses'][$repKey]['type']=isset($aResponse['type']) ? $aResponse['type'] : 'chart';
    }
  }
?>

<?php foreach($aReorederSatisfactions as $iSatId=>$aSatisfaction){ ?>

  <h2><?php echo $aSatisfaction['title'] ?></h2>
  <?php foreach($aSatisfaction['aResponses'] as $repKey=>$aResponse){ ?>
    <?php
      switch($aResponse['type']){
        case 'table':
          echo Yii::app()->controller->renderPartial("adminStats.views.subviews.inc_satisfaction_table",array(
            'repKey'=>$repKey,
            'iSatId'=>$iSatId,
            'aResponse'=>$aResponse,
            'translate'=>$translate,
          ),true);
          break;
        case 'graph':
        default:
          echo Yii::app()->controller->renderPartial("adminStats.views.subviews.inc_satisfaction_graph",array(
            'repKey'=>$repKey,
            'iSatId'=>$iSatId,
            'aResponse'=>$aResponse,
            'translate'=>$translate,
          ),true);
          break;
      }
    ?>
  <?php } ?>
<?php } ?>




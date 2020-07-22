<h1 class="text-primary"><?php echo $titre; ?></h1>
<ul class="nav nav-tabs">
  <li role="presentation"><?php echo CHtml::link(\Yii::t('',"Participation",array(),$className),array("plugins/direct","plugin"=>$className,"function"=>"participation","sid"=>$oSurvey->sid)); ?></li>
  <?php if($showSatisfaction) { ?>
  <li role="presentation" class="active"><a href="#"><?php echo \Yii::t('',"Satisfaction",array(),$className); ?></a></li>
  <?php } ?>
  <?php if($showAdminSurvey) { ?>
  <li role="presentation"><?php echo CHtml::link(\Yii::t('',"Administration",array(),$className),array("admin/survey","sa"=>"view","surveyid"=>$oSurvey->sid)); ?></a></li>
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

  <h2 class="text-primary"><?php echo $aSatisfaction['title'] ?></h2>
  <?php foreach($aSatisfaction['aResponses'] as $repKey=>$aResponse){ ?>
    <?php
      switch($aResponse['type']){
        case 'table':
          echo Yii::app()->controller->renderPartial("{$className}.views.subviews.inc_satisfaction_table",array(
            'repKey'=>$repKey,
            'iSatId'=>$iSatId,
            'aResponse'=>$aResponse,
            'className'=>$className,
          ),true);
          break;
        case 'graph':
        default:
          echo Yii::app()->controller->renderPartial("{$className}.views.subviews.inc_satisfaction_graph",array(
            'repKey'=>$repKey,
            'iSatId'=>$iSatId,
            'aResponse'=>$aResponse,
            'className'=>$className,
          ),true);
          break;
      }
    ?>
  <?php } ?>
<?php } ?>




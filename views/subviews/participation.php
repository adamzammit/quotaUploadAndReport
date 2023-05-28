<h1 class="text-info quickstatpanel-title"><?php echo $titre; ?></h1>
<ul class="nav nav-tabs">
  <li role="presentation" class="active"><a href="#"><?php echo \Yii::t('',"Participation",array(),$className) ?></a></li>
  <?php if($showSatisfaction) { ?>
  <li role="presentation"><?php echo CHtml::link(\Yii::t('',"Satisfaction",array(),$className),array("plugins/direct","plugin"=>"{$className}","function"=>"satisfaction","sid"=>$oSurvey->sid)) ?></li>
  <?php } ?>
  <?php if($showAdminSurvey) { ?>
  <li role="presentation"><?php echo CHtml::link(gT("Administration"),array("surveyAdministration/view","surveyid"=>$oSurvey->sid)); ?></a></li>
  <?php } ?>
</ul>
<?php
    if(!empty($htmlComment))
    {
         echo CHtml::tag("div",array("class"=>'well clearfix'),$htmlComment);
    }
?>
<?php if(!empty($aDailyResponses)){
    Yii::app()->getController()->renderPartial("{$className}.views.subviews.participation_rate",array(
        'title'=>\Yii::t('',"Daily participation",array(),$className),
        'type'=>'',
        'aResponses'=>$aDailyResponses,
        'oSurvey'=>$oSurvey,
        'showExport'=>$showAdmin,
        'showSum'=>true,
        'className'=>$className,
    ));
}?>
<?php if(!empty($aDailyResponsesCumulative)){
    Yii::app()->getController()->renderPartial("{$className}.views.subviews.participation_rate",array(
        'title'=>\Yii::t('',("Daily participation (cumulative)"),array(),$className),
        'type'=>'cumul',
        'aResponses'=>$aDailyResponsesCumulative,
        'oSurvey'=>$oSurvey,
        'showExport'=>false,
        'className'=>$className,
    ));
}?>
<?php if(!empty($aDailyEnter)){
    Yii::app()->getController()->renderPartial("{$className}.views.subviews.participation_rate",array(
        'title'=>\Yii::t('',("Number of connections"),array(),$className),
        'type'=>'enter',
        'aResponses'=>$aDailyEnter,
        'oSurvey'=>$oSurvey,
        'showExport'=>$showAdmin,
        'className'=>$className,
    ));
}?>
<?php if(!empty($aDailyAction)){
    Yii::app()->getController()->renderPartial("{$className}.views.subviews.participation_rate",array(
        'title'=>\Yii::t('',("Daily participation rate"),array(),$className),
        'type'=>'action',
        'aResponses'=>$aDailyAction,
        'oSurvey'=>$oSurvey,
        'showExport'=>$showAdmin,
        'className'=>$className,
    ));
}?>
    <h2 class="text-info quickstatpanel-title"><?php echo \Yii::t('',"Participation rate",array(),$className); ?></h2>
<?php foreach($aResponses as $aResponse){ ?>
    <table class="table table-bordered <?php echo ($aResponse['max']>0) ? "" :" nopercentage"; ?>">
        <thead>
            <tr class="header">
                <?php
                echo CHtml::tag("th",array("class"=>'answer'),$aResponse['title']);
                echo CHtml::tag("td",array("class"=>"cell nbsend"),($aResponse['max']>0) ? \Yii::t('',"Expected participants",array(),$className) :"");
                echo CHtml::tag("td",array("class"=>"cell response"),gT("Responses"));
                echo CHtml::tag("td",array("class"=>"cell rate"),($aResponse['max']>0) ? \Yii::t('',"Participation rate",array(),$className) :"");
                ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($aResponse['data'] as $aResponseData){ ?>
                <tr>
                    <th class="answer"><?php echo $aResponseData['title'] ?></th>
                    <td class="cell nbsend"><?php echo ($aResponseData['max']>0 ? $aResponseData['max'] : ""); ?></td>
                    <td class="cell response"><?php echo $aResponseData['completed'] ?></td>
                    <td class="cell rate"><?php echo ($aResponseData['max']>0) ? round(100*$aResponseData['completed']/$aResponseData['max'],0)."%" : ""; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>

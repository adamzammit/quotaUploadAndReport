<h1><?php echo $titre; ?></h1>
<ul class="nav nav-tabs">
  <li role="presentation" class="active"><a href="#"><?php echo gT("Participation") ?></a></li>
  <?php if($showSatisfaction) { ?>
  <li role="presentation"><?php echo CHtml::link(gT("Satisfaction"),array("plugins/direct","plugin"=>"adminStats","function"=>"satisfaction","sid"=>$oSurvey->sid)) ?></li>
  <?php } ?>
  <?php if($showAdminSurvey) { ?>
  <li role="presentation"><?php echo CHtml::link(gT("Administration"),array("admin/survey","sa"=>"editsurveysettings","surveyid"=>$oSurvey->sid,'#'=>'pluginsettings')); ?></a></li>
  <?php } ?>
</ul>
<?php if(!empty($aDailyResponses)){
    Yii::app()->getController()->renderPartial("adminStats.views.subviews.participation_rate",array(
        'title'=>gT("Taux de participation"),
        'type'=>'',
        'aResponses'=>$aDailyResponses
    ));
}?>
<?php if(!empty($aDailyEnter)){
    Yii::app()->getController()->renderPartial("adminStats.views.subviews.participation_rate",array(
        'title'=>gT("Taux d'entrée journalier"),
        'type'=>'enter',
        'aResponses'=>$aDailyEnter
    ));
}?>
<?php if(!empty($aDailyAction)){
    Yii::app()->getController()->renderPartial("adminStats.views.subviews.participation_rate",array(
        'title'=>gT("Taux d'action journalier"),
        'type'=>'action',
        'aResponses'=>$aDailyAction
    ));
}?>
    <h2><?php echo gT("Taux de participations"); ?></h2>

<?php foreach($aResponses as $aResponse){ ?>
    <table class="table table-bordered">
        <thead>
            <tr class="header">
                <th class="answer"><?php echo $aResponse['title'] ?></th>
                <th class="cell"><?php echo gT("Nombre d'envois") ?></th>
                <th class="cell"><?php echo gT("Responses") ?></th>
                <th class="cell"><?php echo gT("Taux de participation") ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($aResponse['data'] as $aResponseData){ ?>
                <tr>
                    <th class="answer"><?php echo $aResponseData['title'] ?></th>
                    <td class="cell"><?php echo ($aResponseData['max']>0 ? $aResponseData['max'] : "/"); ?></td>
                    <td class="cell"><?php echo $aResponseData['completed'] ?></td>
                    <td class="cell"><?php echo ($aResponseData['max']>0) ? round(100*$aResponseData['completed']/$aResponseData['max'],0)."%" : ""; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>

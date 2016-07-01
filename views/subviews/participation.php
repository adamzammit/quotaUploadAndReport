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
    <table class="table table-bordered <?php echo ($aResponse['max']>0) ? "" :" nopercentage"; ?>">
        <thead>
            <tr class="header">
                <?php
                echo CHtml::tag("th",array("class"=>'answer'),$aResponse['title']);
                echo CHtml::tag("td",array("class"=>"cell nbsend"),($aResponse['max']>0) ? gT("Nombre d'envois") :"");
                echo CHtml::tag("td",array("class"=>"cell response"),gT("Responses"));
                echo CHtml::tag("td",array("class"=>"cell rate"),($aResponse['max']>0) ? gT("Taux de participation") :"");
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

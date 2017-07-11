<h1><?php echo gT('Surveys') ?></h1>

<?php
    //echo CHtml::tag('pre',array(),print_r($aSurveys,1));
    $dataProvider=new CArrayDataProvider($aSurveys, array(
        'keyField' => 'sid',
        'caseSensitiveSort'=>false,
        'sort'=>array(
            'attributes'=>array(
                 'sid', 'title','responsesCount','tokensCount'
            ),
        ),
        'pagination'=>array(
            'pageSize'=>20,
        ),
    ));
    $this->widget('bootstrap.widgets.TbGridView', array(
        'dataProvider'=>$dataProvider,
        'ajaxUpdate' => true,
        'rowCssClassExpression'=>'$data["responsesCount"]==0?"hidden hide":""',
        'columns'=>array(
            array(
                'name'=>'sid',
                'sortable'=>true,
                'header'=>gT("ID"),
                'type' => 'raw',
                'value'=>'CHtml::link($data["sid"],array("plugins/direct","plugin"=>"'.$className.'","function"=>"participation","sid"=>$data["sid"]))',
            ),
            array(
                'name'=>'surveyls_title',
                'sortable'=>true,
                'header'=>gT("Title"),
                'value'=>'$data["title"]',
            ),
            array(
                'name'=>'responsesCount',
                'sortable'=>true,
                'header'=>\Yii::t('',"Responses",array(),$className),
                'value'=>'$data["responsesCount"]',
            ),
            array(
                'name'=>'tokensCount',
                'sortable'=>true,
                'header'=>\Yii::t('',"Expected participants",array(),$className),
                'value'=>'($data["tokensCount"] ? $data["tokensCount"] : "/");',
            ),
        ),
    ));
?>

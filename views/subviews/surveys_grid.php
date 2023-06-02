<?php

    $dataProvider = new CArrayDataProvider($aSurveys, array(
        'keyField' => 'sid',
        'caseSensitiveSort' => false,
        'sort' => array(
            'attributes' => array(
                 'sid', 'title','responsesCount','tokensCount'
            ),
        ),
        'pagination' => array(
            'pageSize' => 20,
        ),
    ));
    $this->widget('bootstrap.widgets.TbGridView', array(
        'dataProvider' => $dataProvider,
        'htmlOptions' => ['class' => 'table-responsive grid-view-quickstats'],
        'ajaxUpdate' => true,
        'rowCssClassExpression' => '$data["responsesCount"]==0?"hidden hide":""',
        'columns' => array(
            array(
                'name' => 'sid',
                'sortable' => true,
                'header' => gT("ID"),
                'type' => 'raw',
                'value' => 'CHtml::link($data["sid"],array("plugins/direct","plugin"=>"' . $className . '","function"=>"participation","sid"=>$data["sid"]))',
                'footer' => ""
            ),
            array(
                'name' => 'surveyls_title',
                'sortable' => true,
                'header' => gT("Title"),
                'value' => '$data["title"]',
                'footer' => '<strong>' . $language["Total"] . '</strong>',
            ),
            array(
                'name' => 'responsesCount',
                'sortable' => true,
                'header' => $language["Responses"],
                'value' => '$data["responsesCount"]',
                'footer' => '<strong>' . $aFooter['responsesCount'] . '</strong>',
            ),
            array(
                'name' => 'tokensCount',
                'sortable' => true,
                'header' => $language["Expected participants"],
                'value' => '($data["tokensCount"] ? $data["tokensCount"] : "/");',
                'footer' => '<strong>' . $aFooter['tokensCount'] . '</strong>',
            ),
            array(
                'name' => 'tokensCount',
                'sortable' => true,
                'header' => $language["Participation rate"],
                'value' => '($data["rateCount"] ? round($data["rateCount"] * 100, 0) . "%" : "/");',
                'footer' => $aFooter['rateCount'] ? '<strong>' . round($aFooter["rateCount"] * 100, 0) . "%"  . '</strong>' : "/",
            ),
        ),
    ));

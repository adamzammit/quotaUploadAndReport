    <h3><?php echo $aResponse['title'] ?></h3>
     <table class="table table-bordered table-striped">
        <thead>
            <tr class="bg-primary">
                <?php
                echo CHtml::tag("th",array("class"=>'answer'),"");
                echo CHtml::tag("td",array("class"=>"cell population"),\Yii::t('',"Population",array(),$className));
                echo CHtml::tag("td",array("class"=>"cell satisfaction"),\Yii::t('',"Satisfaction",array(),$className));
                ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach($aResponse['datas'] as $aData) { ?>
            <tr>
                <?php
                echo CHtml::tag("th",array("class"=>'answer'),$aData['title']);
                echo CHtml::tag("td",array("class"=>"cell response"),$aData['count']);
                echo CHtml::tag("td",array("class"=>"cell satisfaction",'title'=>$aData['average']),($aData['average']) ? number_format($aData['average'],2) : "" );
                ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>

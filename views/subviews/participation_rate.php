    <h2><?php echo $title; ?></h2>

    <?php
      echo CHtml::tag("div",array("id"=>"chart-daily{$type}",'class'=>'chart-day graph jqplot-line'),"",true);
    ?>
    <script>
        $(document).ready(function(){
            $.jqplot.config.enablePlugins = true;
            var dailyRate = [];
            <?php
            $maxValue=0;
            foreach($aResponses as $key=>$value) {
                echo "dailyRate.push(['{$key}', {$value}]);";
                $maxValue=max($maxValue,$value);
                $minDate=(isset($minDate) ? $minDate : $key);
                $maxDate=$key;
            }
            $minDate=date('Y-m-d',strtotime($minDate . " -1 day"));
            $maxDate=date('Y-m-d',strtotime($maxDate . " +1 day"));
            if($oSurvey->startdate && $oSurvey->startdate < $maxDate)
            {
              $minDate=date('Y-m-d',strtotime($oSurvey->startdate));
            }
            if($oSurvey->expires && $oSurvey->expires > $minDate)
            {
              $maxDate=date('Y-m-d',strtotime($oSurvey->expires));
            }
            ?>
           var chartdaily<?php echo $type; ?>  = $.jqplot('chart-daily<?php echo $type; ?>', [dailyRate], {
              //title: 'Sine Data Renderer',
              animate: true,
              series:[{showMarker:true}],
               seriesColors:['#0092dd'],
               highlighter: {
                show: true,
                sizeAdjust: 1,
            },
            grid:{
              background : '#ffffff'
            },
            rendererOptions: {
                      smooth: true
                    },
              axes:{
                xaxis:{
                  renderer:$.jqplot.DateAxisRenderer,
                  tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                  tickInterval:'1 day',
                  min: '<?php echo $minDate; ?>',
                  max: '<?php echo $maxDate; ?>',
                    tickOptions: {
                        angle: -45,
                        formatString:'%d/%m/%y',
                        showMinorTicks:false
                    },
                },
                yaxis:{
                  tickRenderer: $.jqplot.AxisTickRenderer,
                  tickOptions: {
                    showMark:false,
                    showGridline:false,
                    showLabel:false,
                  },
                  pad:1.4,
                  min: 0
                }
              }
          });
        });
    </script>

<h1><?php echo $titre; ?></h1>
<ul class="nav nav-tabs">
  <li role="presentation"><?php echo CHtml::link(gT("Participation"),array("plugins/direct","plugin"=>"adminStats","function"=>"participation","sid"=>$oSurvey->sid)); ?></li>

  <li role="presentation" class="active"><a href="#"><?php echo gT("Satisfaction") ?></a></li>
</ul>

<?php foreach($aResponses as $repKey=>$aResponse){ ?>
  <h2><?php echo $aResponse['title'] ?></h2>
  <?php foreach($aResponse['aSatisfactions'] as $iSatId=>$aSatisfaction){ ?>
    <h3><?php echo $aSatisfaction['title'] ?></h2>
    <?php

      echo CHtml::tag("canvas",array("id"=>"chart-r{$repKey}-s{$iSatId}","width"=>"400","height"=>"100"),"",true);
    ?>
    <?php
      $aLabels=array();
      $aAverage=array();
      foreach($aSatisfaction['datas'] as $aDatas) {
        $aLabels[]="{$aDatas['title']} ({$aDatas['count']})";
        $aAverage[]=$aDatas['average'];
      } ?>
      <script>
      var data = {
          labels: <?php echo json_encode($aLabels) ?>,
          datasets: [
              {
                  label: "",
                  backgroundColor: "#0092dd",
                  data: <?php echo json_encode($aAverage) ?>,
              }
          ]
      };
      var ctx = document.getElementById('<?php echo "chart-r{$repKey}-s{$iSatId}" ?>');
      var myBarChart = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: {
          scales: {
              yAxes: [{
                  ticks: {
                      beginAtZero:true
                  }
              }]
          },
          onAnimationComplete: function()
          {
              console.log("complete");
              this.showTooltip(this.datasets[0].bars, true);
          }
        }
      });
      </script>
  <?php } ?>


<?php } ?>

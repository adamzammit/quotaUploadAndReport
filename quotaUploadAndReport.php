<?php

/**
 * Display a summary of the progress of the survey and allow for complex quota uploading and reporting
 *
 * @author Adam Zammit <adam.zammit@acspri.org.au>
 * @copyright 2025 Adam Zammit <https://www.acspri.org.au/limesurvey>
 * @copyright 2016-2025 Denis Chenu <https://www.sondages.pro>
 * @copyright 2016-2023 Advantage <http://www.advantage.fr>
 * @license AGPL v3
 * @version 6.0.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 */

class quotaUploadAndReport extends PluginBase
{
    protected $storage = "DbStorage";
    protected static $description = "Display a summary of the progress of the survey and allow for complex quota uploading and reporting";
    protected static $name = "quotaUploadAndReport";

    /** @inheritdoc, Allow these public methods */
    public $allowedPublicMethods = array(
        'actionSettings',
        'actionSaveSettings',
    );

    /**
     * @var string[] : this answer (label) must be moved to the end
     * @todo : move this to settings
     */
    private $aPushTokenValue = [
        "Autre",
        "Autres",
        "autre",
        "autres",
        "Other",
        "Others",
        "other",
        "other",
    ];

    /**
     * @var integer : survey ID
     */
    private $iSurveyId;

    /**
     * @var array : render Data
     */
    private $aRenderData = [];

    /**
     * @var string : language for survey
     */
    private $surveyLanguage;

    protected $settings = [
        "docu" => ["type" => "info", "content" => ""],
        "redirectAfterLogin" => [
            "type" => "boolean",
            "label" => "If the user only has statistics permissions, automatically redirect to this plugin.",
            "help" => "Will only redirect if a redirect is not already set in the URL.",
            "default" => 1,
        ],
        "dailyRateEnterAllow" => [
            "type" => "checkbox",
            "label" => "Activate daily participation by default",
            "default" => 1,
        ],
        "dailyRateActionAllow" => [
            "type" => "checkbox",
            "label" => "Activate daily action by default",
            "default" => 0,
        ],
    ];

    /** private boolean isOwnRedit*/
    private $isCurrentUrl = false;

    /** @inheritdoc **/
    public function init()
    {
        if (version_compare(App()->getConfig("versionnumber"), "4", "<")) {
            return;
        }
        $this->subscribe('beforeToolsMenuRender');

        $this->subscribe('afterSuccessfulLogin');
        /* Survey settings */
        $this->subscribe("beforeSurveySettings");
        /* Show page */
        $this->subscribe("newDirectRequest");
    }

    /**
     * Add the link to setçings in plugin settings
     */
    public function beforeSurveySettings()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        /* @Todo move this to own page */
        $surveyId = $this->getEvent()->get('survey');
        $settings = [];
        if (Permission::model()->hasSurveyPermission($surveyId, 'statistics', 'read')) {
            $accesUrl = App()->createUrl("plugins/direct", [
                "plugin" => $this->getName(),
                "function" => "stat",
                "sid" => $surveyId,
            ]);
            if (tableExists("{{survey_{$surveyId}}}")) {
                $settings["statlink"] = [
                    "type" => "info",
                    'content' => CHtml::link($this->translate("Link to statitics"), $accesUrl, array("target" => '_blank','class' => 'btn btn-block btn-default btn-lg')),
                ];
            }
        }
        if (Permission::model()->hasSurveyPermission($surveyId, 'surveysettings', 'read')) {
            $managementUrl = Yii::app()->createUrl(
                'admin/pluginhelper',
                array(
                    'sa' => 'sidebody',
                    'plugin' => get_class($this),
                    'method' => 'actionSettings',
                    'surveyId' => $surveyId
                )
            );
            $settings["management"] = [
                "type" => "info",
                'content' => CHtml::link($this->translate("Manage statistics"), $managementUrl, array('class' => 'btn btn-block btn-default btn-lg')),
            ];
        }
        if (empty($settings)) {
            return;
        }
        $this->getEvent()->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => $settings
        ));
    }

    /**
     * see beforeToolsMenuRender event
     * @deprecated ? See https://bugs.limesurvey.org/view.php?id=15476
     * @return void
     */
    public function beforeToolsMenuRender()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveysettings', 'read')) {
            return;
        }
        if (!Permission::model()->hasSurveyPermission($surveyId, 'surveycontent', 'read')) {
            return;
        }
        $aMenuItem = array(
            'label' => $this->translate('Monitoring and Quota Report'),
            'iconClass' => 'fa fa-bar-chart',
            'href' => Yii::app()->createUrl(
                'admin/pluginhelper',
                array(
                    'sa' => 'sidebody',
                    'plugin' => get_class($this),
                    'method' => 'actionSettings',
                    'surveyId' => $surveyId
                )
            ),
        );
        $menuItem = new \LimeSurvey\Menu\MenuItem($aMenuItem);
        $event->append('menuItems', array($menuItem));
        $event->append('html',['<a href="test">test</a>']);
    }
    
    /** The settings on own page */
    public function actionSettings($surveyId)
    {
        $oSurvey = Survey::model()->findByPk($surveyId);
        if (!$oSurvey) {
            throw new CHttpException(404, gT("This survey does not seem to exist."));
        }
        if (!Permission::model()->hasSurveyPermission($oSurvey->sid, 'surveysettings', 'read')) {
            throw new CHttpException(403);
        }
        if (!Permission::model()->hasSurveyPermission($oSurvey->sid, 'surveycontent', 'read')) {
            throw new CHttpException(403);
        }
        /* Basic data */
        $aData = array(
            'pluginClass' => get_class($this),
            'surveyId' => $surveyId,
        );
        /* Lang */
        $aData['lang'] = array(
            'Close' => $this->translate("Close"),
            'Quick statistics settings' => $this->translate("Quick statistics settings"),
        );

        $aData['aSettings'] = $this->getSettings($oSurvey->sid);
        $aData['updatepermission'] = Permission::model()->hasSurveyPermission($surveyId, 'surveysettings', 'update');
        $aData['form'] = array(
            'action' => App()->createUrl('admin/pluginhelper/sa/sidebody', array('plugin' => get_class($this),'method' => 'actionSaveSettings','surveyId' => $surveyId)),
            'close' => App()->createUrl('surveyAdministration/view', array('surveyid' => $surveyId))
        );
        return $this->renderPartial('admin.settings', $aData, true);
    }

    /** The settings **/
    private function getSettings($surveyId)
    {
        $aSettings = [];
        $oSurvey = Survey::model()->findByPk($surveyId);
        /* var string language to be used */
        $lang = $oSurvey->language;
        $aSettings = [];
        $url = App()->createUrl("plugins/direct", [
            "plugin" => $this->getName(),
            "function" => "stat",
            "sid" => $surveyId,
        ]);
        if (tableExists("{{survey_{$surveyId}}}")) {
            $aSettings["statlink"] = [
                "type" => "info",
                "content" =>
                    "<h5 class='alert alert-info'>" .
                    $this->translate("Link to monitoring and quota report :") .
                    "<a href='{$url}'>{$url}</a></h5>",
            ];
        } else {
            $aSettings["statlink"] = [
                "type" => "info",
                "content" =>
                    "<p class='alert alert-info'>" .
                    $this->translate(
                        "Survey is not activated : no statistics can be shown."
                    ) .
                    "</p>",
            ];
        }
        $aSettings["alternateTitle"] = [
            "type" => "string",
            "label" => $this->translate("Alternate title"),
            "current" => $this->get(
                "alternateTitle",
                "Survey",
                $surveyId,
                ""
            ),
        ];
        $aSettings["numberMax"] = [
            "type" => "int",
            "label" => $this->translate("Expected participation"),
            "help" => $this->translate(
                "Used for participation rate"
            ),
            "htmlOptions" => ["min" => 0],
            "current" => $this->get(
                "numberMax",
                "Survey",
                $surveyId,
                0
            ),
        ];
        $aSettings["CrossTitle"] = [
            "type" => "info",
            "content" =>
                "<h5 class='alert alert-info'>" .
                $this->translate("Participation tab") .
                "</h5>",
        ];
        if ($oSurvey->datestamp == "Y") {
            $aSettings["participationComment"] = [
                "type" => "html",
                "label" => $this->translate(
                    "Description for participation tab"
                ),
                "current" => $this->get(
                    "participationComment",
                    "Survey",
                    $surveyId,
                    ""
                ),
                "height" => "8em",
                "editorOptions" => ["link" => false, "image" => false],
            ];
            $aSettings["dailyRate"] = [
                "type" => "select",
                "label" => $this->translate(
                    "Show the number of completed daily responses."
                ),
                "options" => ["1" => gT("Yes"), "0" => gT("No")],
                "current" => $this->get(
                    "dailyRate",
                    "Survey",
                    $surveyId,
                    1
                ),
            ];
            $aSettings["dailyRateCumulative"] = [
                "type" => "select",
                "label" => $this->translate(
                    "Show the number of completed daily cumulative responses."
                ),
                "options" => ["1" => gT("Yes"), "0" => gT("No")],
                "current" => $this->get(
                    "dailyRateCumulative",
                    "Survey",
                    $surveyId,
                    0
                ),
            ];
            if (
                $this->get(
                    "dailyRateEnterAllow",
                    null,
                    null,
                    $this->settings["dailyRateEnterAllow"]["default"]
                )
            ) {
                $aSettings["dailyRateEnter"] = [
                    "type" => "select",
                    "label" => $this->translate(
                        "Show the number of daily survey opens."
                    ),
                    "options" => ["1" => gT("Yes"), "0" => gT("No")],
                    "current" => $this->get(
                        "dailyRateEnter",
                        "Survey",
                        $surveyId,
                        0
                    ),
                ];
            }
            if (
                $this->get(
                    "dailyRateActionAllow",
                    null,
                    null,
                    $this->settings["dailyRateActionAllow"]["default"]
                )
            ) {
                $aSettings["dailyRateAction"] = [
                    "type" => "select",
                    "label" => $this->translate(
                        "Show the number of daily survey actions (clicked at least once)."
                    ),
                    "options" => ["1" => gT("Yes"), "0" => gT("No")],
                    "current" => $this->get(
                        "dailyRateAction",
                        "Survey",
                        $surveyId,
                        0
                    ),
                ];
            }
        } else {
            $aSettings["dailyRate"] = [
                "type" => "info",
                "label" => $this->translate(
                    "This survey is not date stamped: it is not possible to show daily rates."
                ),
            ];
        }
        /* Token attribute */
        if (tableExists("{{tokens_{$surveyId}}}")) {
            $aRealTokenAttributes = array_keys(
                Yii::app()->db->schema->getTable(
                    "{{tokens_{$surveyId}}}"
                )->columns
            );
            $aRealTokenAttributes = array_combine(
                $aRealTokenAttributes,
                $aRealTokenAttributes
            );
            $aTokenAttributes = array_filter(
                Token::model($surveyId)->attributeLabels()
            );
            $aTokenAttributes = array_diff_key(
                array_replace($aRealTokenAttributes, $aTokenAttributes),
                [
                    "tid" => "tid",
                    "partcipant" => "partcipant",
                    "participant" => "participant",
                    "participant_id" => "participant_id",
                    "firstname" => "firstname",
                    "lastname" => "lastname",
                    "email" => "email",
                    "emailstatus" => "emailstatus",
                    "token" => "token",
                    "language" => "language",
                    "blacklisted" => "blacklisted",
                    "sent" => "sent",
                    "remindersent" => "remindersent",
                    "remindercount" => "remindercount",
                    "completed" => "completed",
                    "usesleft" => "usesleft",
                    "validfrom" => "validfrom",
                    "validuntil" => "validuntil",
                    "mpid" => "mpid",
                ]
            );
            if (!empty($aTokenAttributes)) {
                $aOptions = [];
                foreach ($aTokenAttributes as $attribute => $description) {
                    $aOptions[$attribute] = empty($description)
                        ? $attribute
                        : $description;
                }
                $aSettings["tokenAttributes"] = [
                    "type" => "select",
                    "label" => $this->translate(
                        "Token attributes for pivot (cross-sectional) - graph"
                    ),
                    "options" => $aOptions,
                    "htmlOptions" => ["multiple" => "multiple"],
                    "current" => $this->get(
                        "tokenAttributes",
                        "Survey",
                        $surveyId
                    ),
                ];
            }
        }
        $lang = $oSurvey->language;
        /* Single choice question */
        $oCriteria = new CdbCriteria();
        $oCriteria->condition =
            "parent_qid=0 and t.sid=:sid and questionl10ns.language=:language";
        $oCriteria->params[":sid"] = $oSurvey->sid;
        $oCriteria->params[":language"] = $oSurvey->language;
        $oCriteria->addInCondition("type", ["L", "!"]); // see "*"
        $oCriteria->order = "group_order ASC, question_order ASC";
        $aoSingleQuestion = Question::model()
            ->with("group")
            ->with("questionl10ns")
            ->findAll($oCriteria);
        if (!empty($aoSingleQuestion)) {
            $aSettings["questionCross"] = [
                "type" => "select",
                "label" => $this->translate(
                    "Questions to show frequency tables in report"
                ),
                "options" => CHtml::listData(
                    $aoSingleQuestion,
                    "qid",
                    function ($oSingleQuestion) use ($lang) {
                        return "[" . $oSingleQuestion->title . "] " .
                            viewHelper::flatEllipsizeText(
                                $oSingleQuestion->questionl10ns[$lang]->question,
                                1,
                                80,
                                "...",
                                0.6
                            );
                    }
                ),
                "htmlOptions" => ["multiple" => "multiple"],
                "current" => $this->get(
                    "questionCross",
                    "Survey",
                    $surveyId
                ),
            ];
        }
        $aSettings["IndexTitle"] = [
            "type" => "info",
            "content" =>
                "<h5 class='alert alert-info'>" .
                $this->translate("Create index in database") .
                "</h5>",
        ];
        $aSettings["indexToken"] = [
            "type" => "boolean",
            "label" => $this->translate("Create index on token table"),
            "current" => 0,
        ];
        $aSettings["indexResponse"] = [
            "type" => "boolean",
            "label" => $this->translate("Create index on response table"),
            "current" => 0,
        ];
        $aSettings["QuotaTitle"] = [
            "type" => "info",
            "content" =>
                "<h5 class='alert alert-info'>" .
                $this->translate("Upload a new quota to the survey") .
                "</h5>",
        ];
        return [
            $this->translate('Settings') => $aSettings
        ];
    }
    
    /** Save the settings **/
    public function actionSaveSettings($surveyId)
    {
        $oSurvey = Survey::model()->findByPk($surveyId);
        if (!$oSurvey) {
            throw new CHttpException(404, gT("This survey does not seem to exist."));
        }
        if (!Permission::model()->hasSurveyPermission($oSurvey->sid, 'surveysettings', 'update')) {
            throw new CHttpException(403);
        }
        $aSettings = App()->getRequest()->getPost('quotaUploadAndReport');
        /* Fix not set dropdown */
        $aSettings["tokenAttributes"] = isset($aSettings["tokenAttributes"])
            ? $aSettings["tokenAttributes"]
            : null;
        $aSettings["questionCross"] = isset($aSettings["questionCross"])
            ? $aSettings["questionCross"]
            : null;
        $aSettings["questionNumeric"] = isset($aSettings["questionNumeric"])
            ? $aSettings["questionNumeric"]
            : null;
        $aSettings["tokenAttributesSatisfaction"] = isset(
            $aSettings["tokenAttributesSatisfaction"]
        )
            ? $aSettings["tokenAttributesSatisfaction"]
            : null;
        $aSettings["tokenAttributesSatisfactionTable"] = isset(
            $aSettings["tokenAttributesSatisfactionTable"]
        )
            ? $aSettings["tokenAttributesSatisfactionTable"]
            : null;
        $aSettings["questionCrossSatisfaction"] = isset(
            $aSettings["questionCrossSatisfaction"]
        )
            ? $aSettings["questionCrossSatisfaction"]
            : null;
        $aSettings["questionCrossSatisfactionTable"] = isset(
            $aSettings["questionCrossSatisfactionTable"]
        )
            ? $aSettings["questionCrossSatisfactionTable"]
            : null;
        /* Keep value but remove from save */
        $indexToken = $aSettings['indexToken'];
        $indexResponse = $aSettings['indexResponse'];
        unset($aSettings['indexToken']);
        unset($aSettings['indexResponse']);


        if (isset($_FILES[get_class($this)]["tmp_name"]["fileUpload"]) &&
            !empty($_FILES[get_class($this)]["tmp_name"]["fileUpload"])) {
            //process uploaded quota file
            $file = $_FILES[get_class($this)]["tmp_name"]["fileUpload"];
            $name = $_FILES[get_class($this)]["name"]["fileUpload"];
           
            //check permissions
            if (!Permission::model()->hasSurveyPermission($surveyId, 'quotas', 'create')) {
				die("You do not have permission to create quotas for this survey");
			}
            
            //1. check if quota name (Based on csv file name) already exists
            $safename = trim(substr(preg_replace('/[^A-Za-z0-9 _-]/', '', substr($name,0,-4)),0,100)); //remove .csv and sanitise and max 100 chars
 
            $quotas = Quota::model()->findAll("name LIKE :name AND sid = :sid", [':name' => $safename . "||%", ':sid' => $surveyId]); //gap chars are ||
            
            //2. If it does exist - fail with error
            if (count($quotas) > 0) {
				echo("This quota already exists, please try uploading a different file or changing the filename if you want to duplicate the quota");
				die();
			}
            
            //3. If it doesn't exist start validating upload
            $handle = fopen($file,"r");
            $rcount = 0;
            $header = [];
            $quotaquestions = [];
            $reservednames = ["quota","message","url"];
            $colcount = 0;
            $quotalimit = [];
            $message = [];
            $url = [];
            $quotavals = [];
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($rcount == 0) {
					//validate the header
					$dc = 0;
					foreach($data as $h) {
						$match = 0;
						if (in_array($h,$reservednames)) {
							$match = 1;
						    $header[$dc] = $h;
						} else {
							$questions = Question::model()->findAll("title LIKE :title AND sid = :sid", [':title' => $h, ':sid' => $surveyId]);
							if (count($questions) == 1) {
								//matchy matchy
								$match = 1;
						        $header[$dc] = $questions[0];
						        $quotaquestions[$colcount] = $questions[0];
						        $colcount++;
							}
						}
						if ($match == 0) {
							echo("No matching question or reserved name for: $h");
							die();
						}
						$dc++;
					}
					if ($colcount == 0) {
						echo("No question supplied to set the quota from. Please ensure there is a column in the file matching a question");
						die();
					}
				} else {
				  //validate the data
				  $dc = 0;
				  foreach($data as $h) {
					  if ($header[$dc] == "quota") {
						  //check value matches quota
						  if (!is_numeric($h)) {
							  echo("Quota value: $h is not numeric");
							  die();
						  }
						  $quotalimit[$rcount] = intval($h);
					  }
				      else if ($header[$dc] == "message") {
						  //check value is a string
						  $message[$rcount] = $h;
					  }
					  else if ($header[$dc] == "url") {
						  //check value is a url
						  $url[$rcount] = $h;
					  } else {
						  //check value is a valid label or code for this question
						  $match = 0;
						  foreach($header[$dc]->answers as $answer) {
							  if ($answer->code == $h) {
								  $match = 1;
								  $quotavals[$rcount][$dc] = $answer;
								  break;
							  }
							  else if ($answer->answerl10ns[$oSurvey->language]->answer == $h) {
								  $match = 1;
								  $quotavals[$rcount][$dc] = $answer;
								  break;
							  }
						  }
						  if ($match == 0) {
							echo("No matching answer for: $h");
							die();
						  }
					  }
					  $dc++;
				  }
				}
				$rcount++;
            }
           
            //4. If upload is valid, create quota records in database.
            
            //start with the first column of quota questions and then loop over all possible column options 
            foreach($quotavals as $rcount => $cols) {
				//create a new quota with count($cols) dimensions
			    $quota = new Quota();
			    $subname = "";
			    foreach($cols as $dc => $answer) {
					$subname .= trim(substr($answer->answerl10ns[$oSurvey->language]->answer,0,48)) . "XX";
				}
			    $quota->name = $safename . "||" . substr($subname,0,-2);
			    $quota->sid = $surveyId;
			   
			    if (isset($quotalimit[$rcount])) {
					$quota->qlimit = $quotalimit[$rcount];
					$quota->active = 1;
					$quota->action = 3;
				} else {
					$quota->qlimit = 0;
					$quota->active = 0;
					$quota->action = 3;
				}
			    
			    $quota->save();
			    
			    $quotals = new QuotaLanguageSetting();
			    
			    $quotals->quotals_quota_id = $quota->id;
			    $quotals->quotals_language = $oSurvey->language;
			    $quotals->quotals_name = $quota->name;
			    
			    if (isset($url[$rcount]) && !empty($url[$rcount])) {
					$quota->autoload_url = 1;
					$quotals->quotals_url = $url[$rcount];
					$quota->save();
				}
 
			    $qmessage = "Sorry your responses have exceeded a quota on this survey.";
				if (isset($message[$rcount]) && !empty($message[$rcount])) {
					$qmessage = $message[$rcount];
				}
				
				$quotals->quotals_message = $qmessage;
				$quotals->save();
		    
				foreach($cols as $dc => $answer) {
					$quotamember = new QuotaMember();
					$quotamember->sid = $surveyId;
					$quotamember->qid = $header[$dc]->qid;
					$quotamember->quota_id = $quota->id;
					$quotamember->code = $answer->code;
					$quotamember->save();
				}
			}
	    }

        foreach ($aSettings as $name => $value) {
            /* In order use survey setting, if not set, use global, if not set use default */
            $default = $this->get(
                $name,
                null,
                null,
                isset($this->settings[$name]["default"])
                    ? $this->settings[$name]["default"]
                    : null
            );
            $this->set(
                $name,
                $value,
                "Survey",
                $surveyId,
                $default
            );
        }
        /* Index */
        if ($indexToken) {
            $this->createTokenIndex($surveyId);
        }
        if ($indexResponse) {
            $this->createResponseIndex($surveyId);
        }
        $redirectUrl = Yii::app()->createUrl('admin/pluginhelper/sa/sidebody', array('plugin' => get_class($this),'method' => 'actionSettings','surveyId' => $surveyId));
        Yii::app()->getRequest()->redirect($redirectUrl, true, 303);
    }

    /**
     * Create needed index on token table
     * @param integer $surveyId
     * @return void
     */
    private function createTokenIndex($surveyId)
    {
        if (!tableExists("{{tokens_{$surveyId}}}")) {
            /* @todo flash message*/
            return;
        }
        $allTokenAttributes = array_unique(array_merge(
            (array) $this->get("tokenAttributes", "Survey", $surveyId),
            (array) $this->get("tokenAttributesSatisfaction", "Survey", $surveyId),
            (array) $this->get("tokenAttributesSatisfactionTable", "Survey", $surveyId)
        ));
        $aRealTokenAttributes = array_keys(
            Yii::app()->db->schema->getTable(
                "{{tokens_{$surveyId}}}"
            )->columns
        );
        foreach ($allTokenAttributes as $attribute) {
            if (in_array($attribute, $aRealTokenAttributes)) {
                $indexName = "qickstat_" . $attribute;
                try {
                    App()->getDb()->createCommand()->dropIndex($indexName, "{{tokens_{$surveyId}}}");
                } catch (Exception $ex) {
                    // index not exist : not an error
                }
                if (App()->getDb()->createCommand()->createIndex($indexName, "{{tokens_{$surveyId}}}", $attribute)) {
                    App()->setFlashMessage(
                        sprintf($this->gT("Index created on attribute %s token table."), $attribute),
                        "success"
                    );
                } else {
                    App()->setFlashMessage(
                        sprintf($this->gT("Unblae to create index on attribute %s token table."), $attribute),
                        "warnnng"
                    );
                }
            }
        }
    }

    /**
     * Create needed index on response table
     * @param integer $surveyId
     * @return void
     */
    private function createResponseIndex($surveyId)
    {
        if (!tableExists("{{survey_{$surveyId}}}")) {
            /* @todo flash message*/
            return;
        }
        $allQuestionsIds = array_unique(array_merge(
            (array) $this->get("questionCross", "Survey", $surveyId),
            (array) $this->get("questionCrossSatisfaction", "Survey", $surveyId),
            (array) $this->get("questionCrossSatisfactionTable", "Survey", $surveyId)
        ));
        $aRealReponseAttributes = array_keys(
            Yii::app()->db->schema->getTable(
                "{{survey_{$surveyId}}}"
            )->columns
        );

        foreach ($allQuestionsIds as $qid) {
            $oQuestion = Question::model()->find("qid = :qid", [':qid' => $qid]);
            if (!$oQuestion) {
                continue;
            }
            $column = $surveyId . "X" . $oQuestion->gid . "X" . $oQuestion->qid;
            if (in_array($column, $aRealReponseAttributes)) {
                $indexName = "qickstat_" . $column;
                try {
                    App()->getDb()->createCommand()->dropIndex($indexName, "{{survey_{$surveyId}}}");
                } catch (Exception $ex) {
                    // index not exist : not an error
                }
                if (App()->getDb()->createCommand()->createIndex($indexName, "{{survey_{$surveyId}}}", $column)) {
                    App()->setFlashMessage(
                        sprintf($this->gT("Index created on column %s (question %s) response table."), $column, $oQuestion->title),
                        "success"
                    );
                } else {
                    App()->setFlashMessage(
                        sprintf($this->gT("Unable to create index on column %s (question %s) response table."), $column, $oQuestion->title),
                        "warnnng"
                    );
                }
            }
        }
    }

    /**
     * The request action test
     */
    public function newDirectRequest()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        Yii::import("application.helpers.viewHelper");
        if ($this->event->get("target") != __CLASS__) {
            return;
        }
        if (Yii::app()->user->getIsGuest()) {
            $this->isCurrentUrl = true;
            App()->user->setReturnUrl(App()->request->requestUri);
            App()->controller->redirect(["/admin/authentication"]);
        }
        $sAction = $this->event->get("function");
        $this->iSurveyId = $this->api->getRequest()->getParam("sid");
        $oSurvey = Survey::model()->findByPK($this->iSurveyId);
        if (
            App()
                ->getRequest()
                ->getParam("lang")
        ) {
            App()->language = App()
                ->getRequest()
                ->getParam("lang");
            Yii::app()->session["statlanguage"] = App()->language;
        } elseif (Yii::app()->session["statlanguage"]) {
            App()->language = Yii::app()->session["statlanguage"];
        }
        if ($this->iSurveyId && !$oSurvey) {
            throw new CHttpException(
                404,
                gT("The survey does not seem to exist.")
            );
        } elseif ($this->iSurveyId) {
            if (
                !Permission::model()->hasSurveyPermission(
                    $this->iSurveyId,
                    "statistics"
                )
            ) {
                throw new CHttpException(
                    401,
                    gT("You do not have sufficient rights to access this page.")
                );
            }
            if (tableExists("{{survey_{$oSurvey->sid}}}")) {
                $oSurvey = Survey::model()
                    ->with("languagesettings")
                    ->find("sid=:sid", [":sid" => $this->iSurveyId]);
                if (in_array(App()->language, $oSurvey->getAllLanguages())) {
                    $this->surveyLanguage = App()->language;
                } else {
                    $this->surveyLanguage = $oSurvey->language;
                }
                $this->aRenderData["titre"] = $this->get(
                    "alternateTitle",
                    "Survey",
                    $oSurvey->sid,
                    ""
                );
                if (empty($this->aRenderData["titre"])) {
                    $this->aRenderData["titre"] = $oSurvey->getLocalizedTitle();
                }
                $this->aRenderData["oSurvey"] = $oSurvey;
                $sAction = in_array($sAction, [
                    "participation",
                    "satisfaction",
                    "export",
                    "quota",
                    "activate",
                    "deactivate"
                ])
                    ? $sAction
                    : "participation";
            } else {
                $sAction = "list";
            }
        } else {
            $sAction = false;
        }
        switch ($sAction) {
            case "participation":
                $this->actionParticipation();
                break;
            case "activate":
                $this->actionActivate($oSurvey->sid);
                $this->actionQuota();
                break;
  			case "deactivate":
			    $this->actionDeactivate($oSurvey->sid);
            case "quota":
                $this->actionQuota();
                break;
            case "satisfaction":
                $this->actionSatisfaction();
                break;
            case "export":
                $this->actionExportData();
                break;
            case "list":
            default:
                $this->actionList();
                break;
        }
    }

    /**
     * Render quota report
     * @return void (rendering)
     */
    public function actionQuota()
    {
        if (empty($this->aRenderData["oSurvey"])) {
            throw new CHttpException(500);
        }
        $oSurvey = $this->aRenderData["oSurvey"];
        $this->aRenderData["aQuotas"] = $this->getQuotaData(
            $this->iSurveyId
        );
        $this->ownRender("quota");
    }

    
    public function actionDeactivate($iSurveyId)
    {
		$quota_id = $this->api->getRequest()->getParam("quota_id");
		if (is_numeric($quota_id)) {
			$quota_id = intval($quota_id);
            if (Permission::model()->hasSurveyPermission($iSurveyId, 'quotas', 'update')) {
			    $quota = Quota::model()->find("id = :quota_id AND sid = :sid", [':sid' => $iSurveyId, ':quota_id' => $quota_id]);
			    $quota->active = 0;
			    $quota->save();
			}
		}
	}
    
    public function actionActivate($iSurveyId)
    {
		$quota_id = $this->api->getRequest()->getParam("quota_id");
		if (is_numeric($quota_id)) {
			$quota_id = intval($quota_id);
            if (Permission::model()->hasSurveyPermission($iSurveyId, 'quotas', 'update')) {
			    $quota = Quota::model()->find("id = :quota_id AND sid = :sid", [':sid' => $iSurveyId, ':quota_id' => $quota_id]);
			    $quota->active = 1;
			    $quota->save();
			}
		}
	}
   
    /**
     * Get participation for this survey
     * @return void (rendering)
     */
    public function actionParticipation()
    {
        if (empty($this->aRenderData["oSurvey"])) {
            throw new CHttpException(500);
        }
        $oSurvey = $this->aRenderData["oSurvey"];
        if ($oSurvey->datestamp == "Y") {
            if ($this->get("dailyRate", "Survey", $oSurvey->sid, 1)) {
                $aDailyResponses = $this->aRenderData[
                    "aDailyResponses"
                ] = $this->getDailyResponsesRate($this->iSurveyId);
            }
            if ($this->get("dailyRateCumulative", "Survey", $oSurvey->sid, 1)) {
                $aDailyResponses = isset($aDailyResponses)
                    ? $aDailyResponses
                    : $this->getDailyResponsesRate($this->iSurveyId);
                if (!empty($aDailyResponses)) {
                    $aDailyResponsesCumulative = [];
                    $sum = 0;
                    foreach ($aDailyResponses as $date => $nb) {
                        $sum += $nb;
                        $aDailyResponsesCumulative[$date] = $sum;
                    }
                    $this->aRenderData[
                        "aDailyResponsesCumulative"
                    ] = $aDailyResponsesCumulative;
                }
            }
            if (
                $this->get("dailyRateEnter", "Survey", $oSurvey->sid, 0) &&
                $this->get(
                    "dailyRateEnterAllow",
                    null,
                    null,
                    $this->settings["dailyRateEnterAllow"]["default"]
                )
            ) {
                $this->aRenderData[
                    "aDailyEnter"
                ] = $this->getDailyResponsesRate($this->iSurveyId, "startdate");
            }
            if (
                $this->get("dailyRateAction", "Survey", $oSurvey->sid, 0) &&
                $this->get(
                    "dailyRateActionAllow",
                    null,
                    null,
                    $this->settings["dailyRateActionAllow"]["default"]
                )
            ) {
                $this->aRenderData[
                    "aDailyAction"
                ] = $this->getDailyResponsesRate($this->iSurveyId, "datestamp");
            }
        }
        $this->aRenderData["aResponses"] = $this->getParticipationRate(
            $this->iSurveyId
        );
        $this->aRenderData["htmlComment"] = $this->get(
            "participationComment",
            "Survey",
            $oSurvey->sid,
            ""
        );
        $this->ownRender("participation");
    }
    
    
    private function getQuotaData($iSurveyId)
    {
        $oSurvey = Survey::model()->findByPk($iSurveyId);
        $aQuotas = [];
        $canedit = 0;
        if (Permission::model()->hasSurveyPermission($iSurveyId, 'quotas', 'update')) {
			$canedit = 1;
		}
        
        if (Permission::model()->hasSurveyPermission($iSurveyId, 'quotas', 'read')) {		
			$quotas = Quota::model()->findAll("sid = :sid", [':sid' => $iSurveyId]);
			
			$qgroup = [];
			
			foreach($quotas as $q) {
				$qc = stristr($q->name,'||',true);
				if ($qc !== false) {
					$qgroup[$qc][] = $q;
				} else {
					$qgroup[$q->name] = $q;
				}
			}
			
			//generate tables and actions based on permissions
			foreach($qgroup as $name => $quotan) {
				$n = [];
				$n['name'] = $name;
				$n['canedit'] = $canedit;
				if (is_array($quotan)) {
					foreach($quotan as $quota) {
						$r['completed'] = $quota->completeCount;
						$r['limit'] = $quota->qlimit;
						$r['active'] = $quota->active;
						$r['cols'] = explode("XX",substr(stristr($quota->name,"||"),2));
					    $n['colcount'] = count($r['cols']);
						$n['rows'][$quota->id] = $r;
					}
				} else {
					$r['completed'] = $quotan->completeCount;
					$r['limit'] = $quotan->qlimit;
					$r['active'] = $quotan->active;
					$r['cols'] = [$name];
					$n['colcount'] = 1;
					$n['rows'][$quotan->id] = $r;
				}
				
				$aQuotas[] = $n;
				
			}
		}
		return $aQuotas;
	}
    
    protected function getParticipationRate($iSurveyId)
    {
        $oSurvey = Survey::model()->findByPk($iSurveyId);
        /* decompte */
        $aResponses = [];
        /* Total */
        $max = $this->get("numberMax", "Survey", $iSurveyId, 0);
        $source = 'estimate';
        if (intval($max) == 0 && tableExists("{{tokens_{$iSurveyId}}}")) {
            $max = Token::model($iSurveyId)->count(); // see with Token::model($iSurveyId)->empty()->count()
            $source = 'token';
        }
        $ctimes = $this->getCompletionTimes($iSurveyId);
        $aResponses["total"] = [
            "title" => $this->translate("Globally"),
            "max" => $max,
            "data" => [
                [
                    "title" => $this->translate("Total responses"),
                    "max" => $max,
                    "completed" => Response::model($iSurveyId)->count(
                        "submitdate IS NOT NULL"
                    ),
                ],
                [
                    "title" => $this->translate("Total opened"),
                    "max" => $max,
                    "completed" => Response::model($iSurveyId)->count(
                        "startdate IS NOT NULL"
                    ),
                ],
                [
                    "title" => $this->translate("Completion time (average)"),
                    "max" => $max,
                    "completed" => $ctimes['average']
                ],
                [
                    "title" => $this->translate("Completion time (median)"),
                    "max" => $max,
                    "completed" => $ctimes['median']
                ],
                [
                    "title" => $this->translate("Completion time (minimum)"),
                    "max" => $max,
                    "completed" => $ctimes['min']
                ],
                [
                    "title" => $this->translate("Completion time (maximum)"),
                    "max" => $max,
                    "completed" => $ctimes['max']
                ],

            ],
            'source' => $source
        ];
        /* by token */
        $aTokenCross = $this->get("tokenAttributes", "Survey", $iSurveyId);
        if (!empty($aTokenCross) && tableExists("{{tokens_{$iSurveyId}}}")) {
            $aValidAttributes = Token::model($iSurveyId)->attributeLabels();
            foreach ($aTokenCross as $tokenCross) {
                if (array_key_exists($tokenCross, $aValidAttributes)) {
                    /* The list */
                    $aTokenValues = $this->getTokenValues($tokenCross);
                    $aData = [];
                    $globalMax = 0;
                    foreach ($aTokenValues as $sTokenValue) {
                        $max = Token::model($iSurveyId)->count(
                            "$tokenCross=:tokenvalue",
                            [":tokenvalue" => $sTokenValue]
                        );
                        $globalMax += $max;
                        $aData[] = [
                            "title" => viewHelper::flatEllipsizeText(
                                $sTokenValue,
                                true,
                                false
                            ),
                            "max" => $max,
                            "completed" => Token::model($iSurveyId)
                                ->with("responses")
                                ->count(
                                    "$tokenCross=:tokenvalue AND completed!='N' AND completed<>'' AND responses.submitdate IS NOT NULL",
                                    [":tokenvalue" => $sTokenValue]
                                ),
                        ];
                    }
                    $aResponses[$tokenCross] = [
                        "title" => viewHelper::flatEllipsizeText(
                            $aValidAttributes[$tokenCross],
                            true,
                            false
                        ),
                        "max" => $max,
                        "data" => $aData,
                        'source' => 'token'
                    ];
                }
            }
        }
        /* by questions */
        $aQuestionsCross = $this->get("questionCross", "Survey", $iSurveyId);
        if (!empty($aQuestionsCross)) {
            $oCriteria = new CdbCriteria();
            $oCriteria->condition = "t.sid=:sid and questionl10ns.language=:language";
            $oCriteria->params[":sid"] = $oSurvey->sid;
            $oCriteria->params[":language"] = $this->surveyLanguage;
            $oCriteria->addInCondition("type", ["L", "!"]);
            $oCriteria->addInCondition("t.qid", $aQuestionsCross);
            $oCriteria->order = "group_order ASC, question_order ASC";
            $aoSingleQuestion = Question::model()
                ->with("group")
                ->with("questionl10ns")
                ->findAll($oCriteria);
            if (!empty($aoSingleQuestion)) {
                foreach ($aoSingleQuestion as $oSingleQuestion) {
                    $sColumn = "{$oSingleQuestion->sid}X{$oSingleQuestion->gid}X{$oSingleQuestion->qid}";
                    $aData = [];
                    $oAnswers = Answer::model()
                        ->with("answerl10ns")
                        ->findAll([
                        "condition" => "t.qid=:qid and answerl10ns.language=:language",
                        "order" => "sortorder",
                        "params" => [
                            ":qid" => $oSingleQuestion->qid,
                            ":language" => $this->surveyLanguage,
                        ],
                    ]);
                    $globalMax = 0;
                    foreach ($oAnswers as $oAnswer) {
                        $countCriteria = new CdbCriteria();
                        $countCriteria->condition = "submitdate IS NOT NULL";
                        $countCriteria->compare(
                            Yii::app()->db->quoteColumnName($sColumn),
                            $oAnswer->code
                        );
                        $globalMax += $oAnswer->assessment_value;
                        $aData[] = [
                            "title" => viewHelper::flatEllipsizeText(
                                $oAnswer->answerl10ns[$this->surveyLanguage]->answer,
                                true,
                                false
                            ),
                            "max" => $oAnswer->assessment_value,
                            "completed" => Response::model($iSurveyId)->count(
                                $countCriteria
                            ),
                        ];
                    }
                    $aResponses[$oSingleQuestion->title] = [
                        "title" => viewHelper::flatEllipsizeText(
                            $oSingleQuestion->questionl10ns[$this->surveyLanguage]->question,
                            true,
                            false
                        ),
                        "max" => $globalMax,
                        "data" => $aData,
                        'source' => 'estimate'
                    ];
                }
            }
        }
        return $aResponses;
    }
    /**
     * Show Satisfaction for this survey
     * @return void (rendering)
     */
    public function actionSatisfaction()
    {
        if (empty($this->aRenderData["oSurvey"])) {
            throw new CHttpException(500);
        }
        $oSurvey = $this->aRenderData["oSurvey"];
        $aResponses = [];
        /* Global */
        $aQuestionsNumeric = $this->get(
            "questionNumeric",
            "Survey",
            $this->iSurveyId,
            []
        );
        $aData = [];
        $aDataInfos = []; // Use some data for all datas : less easy than $aData['total'][$sColumn]
        foreach ($aQuestionsNumeric as $iQuestionNumeric) {
            /* find the code column */
            $oQuestion = Question::model()->with("questionl10ns")->find(
                "t.qid=:qid AND questionl10ns.language=:language",
                [
                    ":qid" => $iQuestionNumeric,
                    ":language" => $this->surveyLanguage,
                ]
            );
            if ($oQuestion) {
                $maxByQuestion = 0;
                if ($oQuestion->parent_qid) {
                    $oParentQuestion = Question::model()->with("questionl10ns")->find(
                        "t.qid=:qid AND questionl10ns.language=:language",
                        [
                            ":qid" => $oQuestion->parent_qid,
                            ":language" => $this->surveyLanguage,
                        ]
                    );
                    if ($oParentQuestion->type == ";") {
                        $aoSubQuestionX = Question::model()->with("questionl10ns")->findAll(
                            [
                                "condition" =>
                                    "parent_qid=:parent_qid and questionl10ns.language=:language and scale_id=:scale_id",
                                "params" => [
                                    ":parent_qid" => $oParentQuestion->qid,
                                    ":language" => $this->surveyLanguage,
                                    ":scale_id" => 1,
                                ],
                                "index" => "qid",
                            ]
                        );
                        $oCriteria = new CDbCriteria();
                        $oCriteria->condition =
                            "attribute='arrayTextAdaptation'";
                        $oCriteria->addSearchCondition("value", "star%", false);
                        $oCriteria->addInCondition(
                            "qid",
                            CHtml::listData($aoSubQuestionX, "qid", "qid")
                        );
                        $oExistingAttribute = QuestionAttribute::model()->find(
                            $oCriteria
                        );
                        if ($oExistingAttribute) {
                            $maxByQuestion = intval(
                                substr($oExistingAttribute->value, 4)
                            );
                            $oXQuestion = Question::model()->with("questionl10ns")->find(
                                "t.qid=:qid AND questionl10ns.language=:language",
                                [
                                    ":qid" => $oExistingAttribute->qid,
                                    ":language" => $this->surveyLanguage,
                                ]
                            );
                            if ($oXQuestion) {
                                $sColumnName = "{$oParentQuestion->sid}X{$oParentQuestion->gid}X{$oParentQuestion->qid}{$oQuestion->title}_{$oXQuestion->title}";
                            }
                        }
                    } else {
                        $sColumnName = "{$oParentQuestion->sid}X{$oParentQuestion->gid}X{$oParentQuestion->qid}{$oQuestion->title}";
                        switch ($oParentQuestion->type) {
                            case "F":
                                $sQuotedColumn = Yii::app()->db->quoteColumnName(
                                    "code"
                                );
                                $oCriteria = new CDbCriteria();
                                $oCriteria->condition = "qid =:qid";
                                $oCriteria->addCondition(
                                    "concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}"
                                );
                                $oCriteria->params[":qid"] =
                                    $oParentQuestion->qid;
                                $maxByQuestion = max(
                                    CHtml::listData(
                                        Answer::model()->findAll($oCriteria),
                                        "code",
                                        "code"
                                    )
                                );
                                break;
                            case "A":
                                $maxByQuestion = 5;
                                break;
                            case "B":
                                $maxByQuestion = 10;
                                break;
                        }
                    }
                    $sTitle =
                        "<small>" .
                        viewHelper::flatEllipsizeText(
                            $oParentQuestion->questionl10ns[$this->surveyLanguage]->question,
                            true,
                            false
                        ) .
                        "</small> \n" .
                        viewHelper::flatEllipsizeText(
                            $oQuestion->questionl10ns[$this->surveyLanguage]->question,
                            true,
                            false
                        );
                } else {
                    $sColumnName = "{$oQuestion->sid}X{$oQuestion->gid}X{$oQuestion->qid}";
                    $sTitle = viewHelper::flatEllipsizeText(
                        $oQuestion->questionl10ns[$this->surveyLanguage]->question,
                        true,
                        false
                    );
                    if (in_array($oQuestion->type, ["L", "!"])) {
                        $sQuotedColumn = Yii::app()->db->quoteColumnName(
                            "code"
                        );
                        $oCriteria = new CDbCriteria();
                        $oCriteria->condition = "qid =:qid";
                        $oCriteria->addCondition(
                            "concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}"
                        );
                        $oCriteria->params[":qid"] = $oQuestion->qid;
                        $maxByQuestion = max(
                            CHtml::listData(
                                Answer::model()->findAll($oCriteria),
                                "code",
                                "code"
                            )
                        );
                    } elseif ($oQuestion->type == "N") {
                    }
                }
                if (!empty($sColumnName)) {
                    $iCount = $this->getCountNumeric($sColumnName);
                    if ($iCount) {
                        $aDataInfos[$sColumnName] = [
                            "title" => $sTitle,
                            "min" => 0,
                            "max" => max(
                                $maxByQuestion,
                                $this->getMax($sColumnName)
                            ),
                        ];
                        $aData[$sColumnName] = [
                            "title" => $sTitle,
                            "min" => 0,
                            "max" => max(
                                $maxByQuestion,
                                $this->getMax($sColumnName)
                            ),
                            "datas" => [
                                [
                                    "title" => $this->translate(
                                        "Total responses"
                                    ),
                                    "count" => $iCount,
                                    "average" => $this->getAverage(
                                        $sColumnName
                                    ),
                                ],
                            ],
                        ];
                    }
                }
            }
        }
        if (!empty($aData)) {
            $aResponses["total"] = [
                "title" => $this->translate("Globally"),
                "aSatisfactions" => $aData,
                'type' => 'graph'
            ];
        }
        /* Do it for each */
        $aTokenCrossGraph =  (array) $this->get(
            "tokenAttributesSatisfaction",
            "Survey",
            $this->iSurveyId,
            []
        );
        $aTokenCrossTable = (array) $this->get(
            "tokenAttributesSatisfactionTable",
            "Survey",
            $this->iSurveyId,
            []
        );
        $aAllTokenCross = array_unique(array_merge($aTokenCrossGraph, $aTokenCrossTable));
        if (
            !empty($aDataInfos) &&
            !empty($aAllTokenCross) &&
            tableExists("{{tokens_{$this->iSurveyId}}}")
        ) {
            $aValidAttributes = Token::model(
                $this->iSurveyId
            )->attributeLabels();
            $aAllTokenCross = array_intersect(
                array_keys($aValidAttributes),
                $aAllTokenCross
            );
            foreach ($aAllTokenCross as $tokenCross) {
                $aTokenValues = $this->getTokenValues($tokenCross);
                $aData = [];
                foreach ($aDataInfos as $sColumnName => $aDataInfo) {
                    $aData = [];
                    foreach ($aTokenValues as $sTokenValue) {
                        $value = $sTokenValue;
                        $aData[] = [
                            "title" => viewHelper::flatEllipsizeText(
                                $sTokenValue,
                                true,
                                false
                            ),
                            "count" => $this->getCountNumeric(
                                $sColumnName,
                                [$tokenCross => $sTokenValue]
                            ),
                            "average" => $this->getAverage($sColumnName, [
                                $tokenCross => $sTokenValue,
                            ]),
                        ];
                    }
                    if (!empty($aData)) {
                        $aSatisfaction[$sColumnName] = [
                            "title" => $aDataInfos[$sColumnName]["title"],
                            "min" => 0,
                            "max" => $aDataInfos[$sColumnName]["max"],
                            "datas" => $aData,
                        ];
                    }
                }
                if (in_array($tokenCross, $aTokenCrossGraph)) {
                    $aResponses[$tokenCross . "_graph"] = [
                        "title" => viewHelper::flatEllipsizeText(
                            $aValidAttributes[$tokenCross],
                            true,
                            false
                        ),
                        "aSatisfactions" => $aSatisfaction,
                        'type' => 'graph'
                    ];
                }
                if (in_array($tokenCross, $aTokenCrossTable)) {
                    $aResponses[$tokenCross . "_table"] = [
                        "title" => viewHelper::flatEllipsizeText(
                            $aValidAttributes[$tokenCross],
                            true,
                            false
                        ),
                        "aSatisfactions" => $aSatisfaction,
                        'type' => 'table'
                    ];
                }
            }
        }
        /* Recup all question */
        $oCriteria = new CdbCriteria();
        $oCriteria->condition = "t.sid=:sid and questionl10ns.language=:language";
        $oCriteria->select = "qid";
        $oCriteria->params[":sid"] = $oSurvey->sid;
        $oCriteria->params[":language"] = $this->surveyLanguage;
        $oCriteria->addInCondition("type", ["L", "!"]); // see "*"
        $oCriteria->order = "group_order ASC, question_order ASC";
        $aoAllSingleQuestion = Question::model()
            ->with("questionl10ns")
            ->with("group")
            ->findAll($oCriteria);
        $aAllSingleQuestion = CHtml::listData(
            $aoAllSingleQuestion,
            "qid",
            "qid"
        );
        /* Type graphique */
        $aQuestionsCross = (array) $this->get(
            "questionCrossSatisfaction",
            "Survey",
            $this->iSurveyId
        );
        /* Type tableau */
        $aQuestionsCrossTable = (array) $this->get(
            "questionCrossSatisfactionTable",
            "Survey",
            $this->iSurveyId
        );
        $aAllQuestionsCross = array_intersect(
            $aAllSingleQuestion,
            array_unique(array_merge($aQuestionsCross, $aQuestionsCrossTable))
        );
        /* merge grahique + tableau */
        /* All question filter array */
        if (!empty($aDataInfos) && !empty($aAllQuestionsCross)) {
            $oCriteria = new CdbCriteria();
            $oCriteria->condition = "t.sid=:sid and questionl10ns.language=:language";
            $oCriteria->params[":sid"] = $oSurvey->sid;
            $oCriteria->params[":language"] = $this->surveyLanguage;
            $oCriteria->addInCondition("type", ["L", "!"]);
            $oCriteria->addInCondition("t.qid", $aAllQuestionsCross);
            $oCriteria->order = "group_order ASC, question_order ASC";
            $aoSingleQuestion = Question::model()
                ->with("group")
                ->with("questionl10ns")
                ->findAll($oCriteria);
            if (!empty($aoSingleQuestion)) {
                foreach ($aoSingleQuestion as $oSingleQuestion) {
                    $sColumn = "{$oSingleQuestion->sid}X{$oSingleQuestion->gid}X{$oSingleQuestion->qid}";
                    $oAnswers = Answer::model()
                        ->with("answerl10ns")
                        ->findAll([
                        "condition" => "t.qid=:qid and answerl10ns.language=:language",
                        "order" => "sortorder",
                        "params" => [
                            ":qid" => $oSingleQuestion->qid,
                            ":language" => $this->surveyLanguage,
                        ],
                    ]);
                    $aAnswers = Chtml::listData($oAnswers, "code", "answerl10ns.{$this->surveyLanguage}.answer");
                    $aData = [];
                    foreach ($aDataInfos as $sColumnName => $aDataInfo) {
                        $aData = [];
                        foreach ($aAnswers as $sCode => $sAnswer) {
                            $aData[] = [
                                "title" => viewHelper::flatEllipsizeText(
                                    $sAnswer,
                                    true,
                                    false
                                ),
                                "count" => $this->getCountNumeric(
                                    $sColumnName,
                                    [$sColumn => $sCode]
                                ),
                                "average" => $this->getAverage($sColumnName, [
                                    $sColumn => $sCode,
                                ]),
                            ];
                        }
                        if (!empty($aData)) {
                            $aSatisfaction[$sColumnName] = [
                                "title" => $aDataInfos[$sColumnName]["title"],
                                "min" => 0,
                                "max" => $aDataInfos[$sColumnName]["max"],
                                "datas" => $aData,
                            ];
                        }
                    }
                    if (in_array($oSingleQuestion->qid, $aQuestionsCross)) {
                        $aResponses[$sColumn . "_graph"] = [
                            "title" => viewHelper::flatEllipsizeText(
                                $oSingleQuestion->questionl10ns[$this->surveyLanguage]->question,
                                true,
                                false
                            ),
                            "aSatisfactions" => $aSatisfaction,
                            "type" => "graph",
                        ];
                    }
                    if (
                        in_array($oSingleQuestion->qid, $aQuestionsCrossTable)
                    ) {
                        $aResponses[$sColumn . "_table"] = [
                            "title" => viewHelper::flatEllipsizeText(
                                $oSingleQuestion->questionl10ns[$this->surveyLanguage]->question,
                                true,
                                false
                            ),
                            "aSatisfactions" => $aSatisfaction,
                            "type" => "table",
                        ];
                    }
                }
            }
        }
        $this->aRenderData["aResponses"] = $aResponses;
        $aReorderSatisfactions = array();
        foreach ($aResponses as $repKey => $aResponse) {
            foreach ($aResponse['aSatisfactions'] as $iSatId => $aSatisfaction) {
                if (empty($aReorderSatisfactions[$iSatId])) {
                    $aReorderSatisfactions[$iSatId] = array(
                        'title' => $aSatisfaction['title'],
                        'aResponses' => array()
                    );
                }
                $aReorderSatisfactions[$iSatId]['aResponses'][$repKey] = $aResponses[$repKey]['aSatisfactions'][$iSatId];
                $aReorderSatisfactions[$iSatId]['aResponses'][$repKey]['title'] = $aResponses[$repKey]['title'];
                $aReorderSatisfactions[$iSatId]['aResponses'][$repKey]['type'] = isset($aResponse['type']) ? $aResponse['type'] : 'chart';
            }
        }
        $this->aRenderData["aReorderSatisfactions"] = $aReorderSatisfactions;
        $this->aRenderData["htmlComment"] = $this->get(
            "satisfactionComment",
            "Survey",
            $oSurvey->sid,
            ""
        );
        $this->ownRender("satisfaction");
    }
    /**
     * Export in CSV the fayly response rate
     */
    public function actionExportData()
    {
        if (empty($this->aRenderData["oSurvey"])) {
            throw new CHttpException(500);
        }
        $oSurvey = $this->aRenderData["oSurvey"];
        $exportType = "dayresponse";
        $type = App()
            ->getRequest()
            ->getParam("state");
        switch ($type) {
            case "enter":
                $state = "startdate";
                break;
            case "action":
                $state = "datestamp";
                break;
            default:
                $state = "submitdate";
        }
        $aDatas = $this->getDailyResponsesRate($oSurvey->sid, $state);
        $aHeader = [gT("Day"), $this->translate("Nb")];
        header("Content-Disposition: attachment; filename=" . $state . ".csv");
        header("Content-type: text/comma-separated-values; charset=UTF-8");
        echo implode(",", $aHeader) . PHP_EOL;
        foreach ($aDatas as $key => $value) {
            echo $key . "," . $value . PHP_EOL;
        }
        die();
    }
    
    /**
     * Get the completion times from the survey
     * 
     * @param int iSurveyId: the id of the survey
     * @return array (completion time average and median)
     */
    private function getCompletionTimes($iSurveyId)
    {
		$aCompletionTimes = Yii::app()
		    ->db->createCommand()
		    ->select(
		        "(submitdate - startdate) as t"
		    )
            ->from("{{survey_{$iSurveyId}}} s")
            ->where("submitdate IS NOT NULL")
            ->order("t")
            ->queryAll();
       
        $aReturn = ["average" => "No completions yet", "median" => "No completions yet", "min" => "No completions yet", "max" => "No completions yet"]; 
        $count = 0;
        $total = 0;
        foreach($aCompletionTimes as $aCompletionTime) {
			$total += $aCompletionTime["t"];
			$count++;
		}
		if ($count > 0) {
			$aReturn["average"] = gmdate("H:i:s",intval($total/$count));
			
			if ($count % 2 == 0) {
			    $medval = $aCompletionTimes[($count / 2)]["t"];
			} else {
			    $medval = ($aCompletionTimes[floor($count / 2)]["t"] +
			               $aCompletionTimes[ceil($count / 2)]["t"]) / 2.0;
            }
			$aReturn["median"] = gmdate("H:i:s",intval($medval));
			$aReturn["min"] = gmdate("H:i:s",intval($aCompletionTimes[0]["t"]));
			$aReturn["max"] = gmdate("H:i:s",intval($aCompletionTimes[$count - 1]["t"]));
		}
		return $aReturn;
	}
    
    /**
     * Get the reponse by day
     * @param int iSurveyId : the id of the survey
     * @param string state : date to take into account
     * @return array (response by day)
     */
    private function getDailyResponsesRate($iSurveyId, $state = "submitdate")
    {
        $aDailyResponsesRateArray = Yii::app()
            ->db->createCommand()
            ->select(
                "DATE({$state}) as " .
                    Yii::app()->db->quoteColumnName("date") .
                    ",COUNT(*) AS " .
                    Yii::app()->db->quoteColumnName("nb")
            )
            ->from("{{survey_{$iSurveyId}}} s")
            ->where("{$state} IS NOT NULL")
            ->order("date")
            ->group("date")
            ->queryAll();
        $aDailyResponsesRate = [];
        foreach ($aDailyResponsesRateArray as $aDailyResponse) {
            $aDailyResponsesRate[$aDailyResponse["date"]] =
                $aDailyResponse["nb"];
        }
        return $aDailyResponsesRate;
    }
    /**
     * Get list of statictics survey for this user
     * @return void (rendering)
     */
    public function actionList()
    {
        $this->aRenderData["titre"] = gt("Surveys");
        $aStatSurveys = $this->getSurveyList();
        $aFinalSurveys = [];
        $aFooter = [
            'responsesTotal' => 0, // Total
            'responsesCount' => 0, // Submitted
            'tokensCount' => 0, // Tokens or atented
            'responsesTokenTotal' => 0,
            'responsesTokenCount' => 0,
            'rateTotal' => "",
            'rateCount' => "",
        ];
        foreach ($aStatSurveys as $aStatSurvey) {
            $aStatSurvey["responsesTotal"] = Response::model(
                $aStatSurvey["sid"]
            )->count();
            $aFooter['responsesTotal'] += $aStatSurvey["responsesTotal"];
            $aStatSurvey["responsesCount"] = Response::model(
                $aStatSurvey["sid"]
            )->count("submitdate IS NOT NULL");
            $aFooter['responsesCount'] += $aStatSurvey["responsesCount"];
            $aStatSurvey["tokensCount"] = $this->get(
                "numberMax",
                "Survey",
                $aStatSurvey["sid"],
                0
            );
            if (intval($aStatSurvey["tokensCount"]) == 0 && tableExists("{{tokens_{$aStatSurvey["sid"]}}}")) {
                $aStatSurvey["tokensCount"] = Token::model(
                    $aStatSurvey["sid"]
                )->count();
            }
            if ($aStatSurvey["tokensCount"] > 0) {
                $aStatSurvey["rateTotal"] = $aStatSurvey["responsesTotal"] / $aStatSurvey["tokensCount"];
                $aStatSurvey["rateCount"] = $aStatSurvey["responsesCount"] / $aStatSurvey["tokensCount"];
            } else {
                $aStatSurvey["rateTotal"] = "";
                $aStatSurvey["rateCount"] = "";
            }
            if (intval($aStatSurvey["responsesCount"]) > 0 || $aStatSurvey["tokensCount"] > 0) {
                $aFinalSurveys[] = $aStatSurvey;
                $aFooter['tokensCount'] += $aStatSurvey["tokensCount"];
                if ($aStatSurvey["tokensCount"] > 0) {
                    $aFooter['responsesTokenTotal'] += $aStatSurvey["responsesTotal"];
                    $aFooter['responsesTokenCount'] += $aStatSurvey["responsesCount"];
                }
            }
        }
        if ($aFooter['tokensCount'] > 0) {
            $aFooter["rateTotal"] = $aFooter["responsesTotal"] / $aFooter["tokensCount"];
            $aFooter["rateCount"] = $aFooter["responsesCount"] / $aFooter["tokensCount"];
        }
        $this->aRenderData["aSurveys"] = $aFinalSurveys;
        $this->aRenderData["surveysGrid"] = $this->renderPartial(
            'subviews.surveys_grid',
            [
                'aSurveys' => $aFinalSurveys,
                'aFooter' => $aFooter,
                'language' => $this->getRenderLanguageStrings(),
                'className' => get_class($this)
            ],
            true
        );
        $this->ownRender("list_surveys");
    }

    public function afterSuccessfulLogin()
    {
        if (!$this->get('redirectAfterLogin', null, null, true)) {
            return;
        }
        if (!$this->onlyStatAccess()) {
            return;
        }
        if ($this->isCurrentUrl) {
            return;
        }
        $returnUrl = App()->user->getReturnUrl();
        $pluginUrl = App()->createUrl("plugins/direct");
        if (empty($returnUrl) || substr($returnUrl, 0, strlen($pluginUrl)) !== $pluginUrl) {
            $url = App()->createUrl("plugins/direct", [
                "plugin" => $this->getName(),
                "function" => "list",
            ]);
            App()->user->setReturnUrl($url);
        }
    }

    /**
     * Test if have only statistics access
     * @todo : use a global settings ?
     * @return boolean
     */
    private function onlyStatAccess()
    {
        if (Yii::app() instanceof CConsoleApplication) {
            return;
        }
        if (!Yii::app()->session["loginID"]) {
            return;
        }
        $countPermission = Permission::model()->count(
            "uid=:uid AND permission NOT LIKE :permission AND entity='global' AND (create_p > 0 or read_p > 0 or update_p > 0 or delete_p > 0 or import_p > 0 or import_p > 0)",
            [":uid" => Yii::app()->session["loginID"], ":permission" => "auth%"]
        );
        $countSurveyPermission = Permission::model()->count(
            "uid=:uid AND (permission NOT LIKE :permission1 AND permission NOT LIKE :permission2 AND permission NOT LIKE :permission3) AND entity='Survey' AND (create_p > 0 or read_p > 0 or update_p > 0 or delete_p > 0 or import_p > 0 or import_p > 0)",
            [
                ":uid" => Yii::app()->session["loginID"],
                ":permission1" => "statistics",
                ":permission2" => "survey",
                ":permission3" => "quotas",
            ]
        );

        return !((bool) $countPermission || (bool) $countSurveyPermission);
    }
    /**
     * rendering a file in plugin view
     * @param string $type : the statitistics type, file to render (in views/subviews)
     * @return void
     */
    private function ownRender($type)
    {
        Yii::setPathOfAlias(
            "quotaUploadAndReport",
            dirname(__FILE__)
        );
        $oEvent = $this->event;
        $this->updateTwigConfiguration();
        $this->aRenderData[
            "assetUrl"
        ] = $sAssetUrl = Yii::app()->assetManager->publish(
            dirname(__FILE__) . "/assets"
        );
        $this->aRenderData["jqplotUrl"] = Yii::app()->assetManager->publish(
            dirname(__FILE__) . "/vendor/jquery.jqplot"
        );
        $this->aRenderData["subview"] = "subviews.{$type}";
        $this->aRenderData["type"] = $type;
        $this->aRenderData["surveyList"] = $this->getSurveyList();
        $this->aRenderData["showSatisfaction"] = !empty(
            $this->get("questionNumeric", "Survey", $this->iSurveyId, [])
        );
        $this->aRenderData["showAdminSurvey"] =
            Permission::model()->hasSurveyPermission(
                $this->iSurveyId,
                "surveysettings",
                "update"
            ) && !$this->onlyStatAccess();
        $this->aRenderData["showExport"] = Permission::model()->hasSurveyPermission($this->iSurveyId, "response", "export");
        $this->aRenderData["showAdmin"] = !$this->onlyStatAccess();
        $this->aRenderData["className"] = self::$name;
        $this->subscribe("getPluginTwigPath", "getPluginTwigPathRender");
        if (empty($this->iSurveyId)) {
            $this->renderNoSurvey();
        }
        $twigRenderData = ["aStatPanel" => $this->aRenderData];
        $oSurvey = Survey::model()->findByPK($this->iSurveyId);
        $language = App()->getLanguage();
        if (!in_array($language, $oSurvey->getAllLanguages())) {
            $language = $oSurvey->language;
        }
        $twigRenderData["aSurveyInfo"] = getSurveyInfo(
            $this->iSurveyId,
            $language
        );
        $twigRenderData["aSurveyInfo"]["include_content"] = "quickstatpanel";
        $twigRenderData["aSurveyInfo"]["showprogress"] = false;
        Yii::app()->setConfig("surveyID", $this->iSurveyId);
        $twigRenderData["aSurveyInfo"]["alanguageChanger"]["show"] = false;
        $alanguageChangerDatas = getLanguageChangerDatas(App()->language);
        if ($alanguageChangerDatas) {
            $twigRenderData["aSurveyInfo"]["alanguageChanger"]["show"] = true;
            $twigRenderData["aSurveyInfo"]["alanguageChanger"][
                "datas"
            ] = $alanguageChangerDatas;
        }
        $twigRenderData["aStatPanel"]["userName"] = Yii::app()->user->getName();
        $twigRenderData["aStatPanel"]["surveyUrl"] = App()->createUrl(
            "plugins/direct",
            [
                "plugin" => $this->getName(),
                "function" => "stat",
                "sid" => $this->iSurveyId,
            ]
        );
        $twigRenderData["aStatPanel"]["language"] = $this->getRenderLanguageStrings();
        $languageData = getLanguageDetails(App()->getLanguage());
        $twigRenderData["aStatPanel"]["jqplotDateFormat"] = $this->getJqplotDateFormat($languageData['dateformat']);

        App()->clientScript->registerScriptFile(
            Yii::app()->getConfig("generalscripts") . "nojs.js",
            CClientScript::POS_HEAD
        );
        Template::model()->getInstance(null, $this->iSurveyId);
        Yii::app()->twigRenderer->renderTemplateFromFile(
            "layout_global.twig",
            $twigRenderData,
            false
        );
        Yii::app()->end();
    }
    private function renderNoSurvey()
    {
        $lang = Yii::app()->language;
        $aLanguages = getLanguageDataRestricted(false, "short");
        if (!isset($aLanguages[$lang])) {
            $lang = App()->getConfig("defaultlang");
            Yii::app()->language = $lang;
        }
        $oTemplate = Template::model()->getInstance(
            getGlobalSetting("defaulttheme")
        );
        $twigRenderData = ["aStatPanel" => $this->aRenderData];
        $twigRenderData["aSurveyInfo"] = [
            "oTemplate" => $oTemplate,
            "sSiteName" => Yii::app()->getConfig("sitename"),
            "sSiteAdminName" => Yii::app()->getConfig("siteadminname"),
            "sSiteAdminEmail" => Yii::app()->getConfig("siteadminemail"),
            "bShowClearAll" => false,
            "surveyls_title" => Yii::app()->getConfig("sitename"),
        ];
        $twigRenderData["aSurveyInfo"]["include_content"] = "quickstatpanel";
        $twigRenderData["aSurveyInfo"]["showprogress"] = false;
        $twigRenderData["aSurveyInfo"]["active"] = true;
        $twigRenderData["aStatPanel"]["userName"] = Yii::app()->user->getName();
        $twigRenderData["aStatPanel"]["surveyUrl"] = App()->createUrl(
            "plugins/direct",
            ["plugin" => $this->getName(), "function" => "stat"]
        );
        Yii::app()->clientScript->registerScriptFile(
            Yii::app()->getConfig("generalscripts") . "nojs.js",
            CClientScript::POS_HEAD
        );
        Yii::app()->twigRenderer->renderTemplateFromFile(
            "layout_global.twig",
            $twigRenderData,
            false
        );
        Yii::app()->end();
    }

    public function getPluginTwigPath()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $viewPath = dirname(__FILE__) . "/twig";
        $this->getEvent()->append("add", [$viewPath]);
    }

    public function getPluginTwigPathRender()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $this->getPluginTwigPath();
        $forcedPath = dirname(__FILE__) . "/twig_replace";
        $this->getEvent()->append("replace", [$forcedPath]);
    }

    public function getValidScreenFiles()
    {
        if (!$this->getEvent()) {
            throw new CHttpException(403);
        }
        $this->subscribe("getPluginTwigPath");
        if (
            $this->getEvent()->get("type") != "view" ||
            ($this->getEvent()->get("screen") &&
                $this->getEvent()->get("screen") != "welcome")
        ) {
            return;
        }
        $this->getEvent()->append("add", [
            "subviews/quickstatpanel/statpanel_about.twig",
            "subviews/quickstatpanel/statpanel_usermenu.twig",
            "subviews/quickstatpanel/statpanel_param.twig",
        ]);
    }

    /**
     * Add needed function to twig
     */
    private function updateTwigConfiguration()
    {
        $twigRenderer = App()->twigRenderer;
        $twigRenderer->addFilters(
            [
                'number_format',
                'date_format'
            ]
        );
    }

    /**
     * Return the survey with allowed access
     */
    private function getSurveyList()
    {
        if (!Yii::app()->user->getId()) {
            throw new CHttpException(401);
        }
        static $aStatSurveys;
        if (null !== $aStatSurveys) {
            return $aStatSurveys;
        }
        $oCriteria = new CdbCriteria();
        $oCriteria->select = ['sid', 'active', 'language'];
        $oCriteria->with = ['languagesettings' => [
                'select' => 'surveyls_title',
                'where' => 't.language = languagesettings.language'
            ]
        ];
        $oCriteria->condition = "active=:active";
        $oCriteria->params[":active"] = "Y";
        if (!Permission::model()->hasGlobalPermission("surveys", "read")) {
            $oCriteria->addCondition(
                "sid IN (SELECT entity_id FROM {{permissions}} WHERE entity = :entity AND  uid = :uid AND permission = :permission AND read_p = 1)"
            );
            $oCriteria->params[":entity"] = "Survey";
            $oCriteria->params[":uid"] = Yii::app()->user->getId();
            $oCriteria->params[":permission"] = "statistics";
            $oCriteria->compare(
                "owner_id",
                Yii::app()->user->getId(),
                false,
                "OR"
            );
        }
        $aSurveys = Survey::model()
            ->findAll($oCriteria);
        $aStatSurveys = [];
        if (!empty($aSurveys)) {
            foreach ($aSurveys as $oSurvey) {
                if (tableExists("{{survey_{$oSurvey->sid}}}")) {
                    $title = $this->get(
                        "alternateTitle",
                        "Survey",
                        $oSurvey->sid,
                        ""
                    );
                    if (empty($title)) {
                        $title = $oSurvey->defaultlanguage->surveyls_title;
                    }
                    $aStatSurveys[] = array_merge(
                        $oSurvey->attributes,
                        $oSurvey->defaultlanguage->attributes,
                        ["title" => $title]
                    );
                }
            }
        }
        return $aStatSurveys;
    }

    /**
     * Update plugin settings for the link and lang
     */
    public function getPluginSettings($getValues = true)
    {
        $url = $this->api->createUrl("plugins/direct", [
            "plugin" => $this->getName(),
            "function" => "list",
        ]);
        $this->settings["docu"]["content"] =
            "<p class='alert alert-info'>" .
            $this->translate("The link to the statistics is:") .
            "<a href='{$url}'>{$url}</a></p>";
        $this->settings["dailyRateEnterAllow"]["label"] = $this->translate(
            "Activate daily participation"
        );
        $this->settings["dailyRateEnterAllow"]["help"] = $this->translate(
            "Allow surveys to enable the daily participation by survey report"
        );
        $this->settings["dailyRateActionAllow"]["label"] = $this->translate(
            "Activate daily action"
        );
        $this->settings["dailyRateActionAllow"]["help"] = $this->translate(
            "Allow surveys to enable the daily action by survey report"
        );
        return parent::getPluginSettings($getValues);
    }

    /**
     * Get the lanuage strings for twig render
     * @return string[] : string in english => translated string
     */
    private function getRenderLanguageStrings()
    {
        return array(
            "Participation" => $this->translate("Participation rates"),
            "Quota report" => $this->translate("Quota report"),
            "Satisfaction" => $this->translate("Question monitoring"),
            "Administration" => $this->translate("Administration"),
            "Population" => $this->translate("Population"),
            "Export" => $this->translate("Export"),
            "Daily participation" => $this->translate("Daily participation"),
            "Daily participation (cumulative)" => $this->translate("Daily participation (cumulative)"),
            "Number of connections" => $this->translate("Daily opens"),
            "Daily participation rate" => $this->translate("Daily opens with at least one action"),
            "Expected participants" => $this->translate("Expected participants"),
            "Invitation sent" => $this->translate("Invitation sent"),
            "Responses" => $this->translate("Responses"),
            "Globally" => $this->translate("Globally"),
            "Participation rate" => $this->translate("Participation rate"),
            'Total' => $this->translate("Total")
        );
    }

    /**
     * Get the moyenne for a numeric question type
     * @param : $sColumn : column title
     * @return float|false
     */
    private function getAverage($sColumn, $aConditions = null)
    {
        //~ $aAverage=array(); // Go to cache ?
        //~ if(isset($aAverage[$sColumn]))
        //~ return $aAverage[$sColumn];
        $sQuotedColumn = Yii::app()->db->quoteColumnName($sColumn);
        $iTotal = $this->getCountNumeric($sColumn, $aConditions);
        if ($iTotal <= 0) {
            $average = false;
            return $average;
        }
        $oCriteria = new CDbCriteria();
        $oCriteria->select = "SUM({$sQuotedColumn}) as SUM";
        $oCriteria->condition = "submitdate IS NOT NULL";
        $oCriteria->addCondition(
            "concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}"
        );
        if (empty($aConditions)) {
            $iSum = (int) Yii::app()
                ->db->getCommandBuilder()
                ->createFindCommand(
                    SurveyDynamic::model($this->iSurveyId)->getTableSchema(),
                    $oCriteria
                )
                ->queryScalar();
        } else {
            foreach ($aConditions as $column => $values) {
                if (is_array($values)) {
                    $oCriteria->addInCondition($column, $values);
                } else {
                    $oCriteria->compare($column, $values);
                }
            }
            if (tableExists("{{tokens_{$this->iSurveyId}}}")) {
                /* Manually construct where command ... */
                $sSelect =
                    "submitdate IS NOT NULL" .
                    " AND concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}";
                $params = [];
                $countParams = 1;
                foreach ($aConditions as $column => $values) {
                    if (is_array($values)) {
                        $valParams = [];
                        foreach ($values as $value) {
                            $valParams[] = ":p{$countParams}";
                            $params[":p{$countParams}"] = $value;
                            $countParams++;
                        }
                        $sSelect .=
                            " AND " .
                            Yii::app()->db->quoteColumnName($column) .
                            " IN (" .
                            implode(",", $valParams) .
                            ")";
                    } else {
                        $params[":p{$countParams}"] = $values;
                        $sSelect .=
                            " AND " .
                            Yii::app()->db->quoteColumnName($column) .
                            "= :p{$countParams}";
                        $countParams++;
                    }
                }
                $iSum = (int) Yii::app()
                    ->db->createCommand()
                    ->select("SUM({$sQuotedColumn}) as SUM")
                    ->from("{{survey_{$this->iSurveyId}}} s")
                    ->join("{{tokens_{$this->iSurveyId}}} t", "s.token=t.token")
                    ->where($sSelect, $params)
                    ->queryScalar();
            } else {
                $iSum = (int) Yii::app()
                    ->db->getCommandBuilder()
                    ->createFindCommand(
                        SurveyDynamic::model(
                            $this->iSurveyId
                        )->getTableSchema(),
                        $oCriteria
                    )
                    ->queryScalar();
            }
        }
        if ($iTotal > 0) {
            $average = $iSum / $iTotal;
        } else {
            $average = false;
        }
        return $average;
    }
    /**
     * Get the maximum numeric value question type
     * @todo fix it, control it
     * @param : $sColumn : column title
     * @return float|false
     */
    private function getMax($sColumn, $aCondition = null)
    {
        $aMax = []; // Go to cache ?
        //~ if(isset($aMax[$sColumn]))
        //~ return $aMax[$sColumn];
        $sQuotedColumn = Yii::app()->db->quoteColumnName($sColumn);
        $oCriteria = new CDbCriteria();
        $oCriteria->select = "MAX({$sQuotedColumn})";
        $oCriteria->condition = "submitdate IS NOT NULL";
        $oCriteria->addCondition(
            "concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}"
        );
        $iMax = Yii::app()
            ->db->getCommandBuilder()
            ->createFindCommand(
                SurveyDynamic::model($this->iSurveyId)->getTableSchema(),
                $oCriteria
            )
            ->queryScalar();
        if ($iMax > 0) {
            $aMax[$sColumn] = $iMax;
        } else {
            $aMax[$sColumn] = false;
        }
        return $aMax[$sColumn];
    }
    /**
     * Get the count of answered for a numeric question type (only numeric answers)
     * @param string $sColumn : column title
     * @param array $aCondition
     * @return integer
     */
    private function getCountNumeric($sColumn, $aConditions = null)
    {
        //~ $aCountNumeric=array(); // Go to cache ?
        //~ if(isset($aCountNumeric[$sColumn]))
        //~ return $aCountNumeric[$sColumn];
        $sQuotedColumn = Yii::app()->db->quoteColumnName($sColumn);
        $oCriteria = new CDbCriteria();
        $oCriteria->condition = "submitdate IS NOT NULL";
        $oCriteria->addCondition(
            "concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}"
        );
        if (empty($aConditions)) {
            $iCountNumeric = (int) Response::model($this->iSurveyId)->count(
                $oCriteria
            );
        } else {
            foreach ($aConditions as $column => $values) {
                if (is_array($values)) {
                    $oCriteria->addInCondition($column, $values);
                } else {
                    $oCriteria->compare($column, $values);
                }
            }
            if (tableExists("{{tokens_{$this->iSurveyId}}}")) {
                $iCountNumeric = (int) Response::model($this->iSurveyId)
                    ->with("token")
                    ->count($oCriteria);
            } else {
                $iCountNumeric = (int) Response::model($this->iSurveyId)->count(
                    $oCriteria
                );
            }
        }
        return $iCountNumeric;
    }

    /**
     * get the array of token values
     * @param string attribute to take (column name)
     */
    private function getTokenValues($attribute)
    {
        $oTokenValues = Token::model($this->iSurveyId)->findAll([
            "select" => $attribute,
            "condition" => "$attribute !='' and $attribute is not null",
            "group" => $attribute,
            "order" => $attribute,
            "distinct" => true,
        ]);
        if ($oTokenValues) {
            $aTokenValues = CHtml::listData(
                $oTokenValues,
                $attribute,
                $attribute
            );
            foreach ($this->aPushTokenValue as $sPushToken) {
                if (array_key_exists($sPushToken, $aTokenValues)) {
                    unset($aTokenValues[$sPushToken]);
                    $aTokenValues[$sPushToken] = $sPushToken;
                }
            }
            return $aTokenValues;
        }
    }

    /**
     * Translate a plugin string
     * @param string $string to translate
     * @return string
     */
    private function translate($string)
    {
        return $this->gT($string, 'unescaped');
    }

    /**
     * getDateformat from number to jqplot formatString
     * @see surveytranslator_helper/ getDateFormatData
     * @var integer getLanguageData[lang][dateformat]
     * @return string
     **/
    private function getJqplotDateFormat($iDateFormat)
    {
        // see https://web.archive.org/web/20230327045702/http://www.jqplot.com/docs/files/plugins/jqplot-dateAxisRenderer-js.html
        $aDateFormats = array(
            1 => '%d.%m.%Y',
            2 => '%d-%m-%Y',
            3 => '%Y.%m.%d',
            4 => '%#d.%#m.%Y',
            5 => '%d/%m/%Y',
            6 => '%Y-%m-%d',
            7 => '%Y/%m/%d',
            8 => '%#d/%#m/%Y',
            9 => '%m-%d-%Y',
            10 => '%m.%d.%Y',
            11 => '%m/%d/%Y',
            12 => '%#d-%#m-%Y'
        );
        if (isset($aDateFormats[$iDateFormat])) {
            return $aDateFormats[$iDateFormat];
        }
        return $aDateFormats[6];
    }
}

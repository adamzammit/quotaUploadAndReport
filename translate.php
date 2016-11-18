<?php
/**
 * A class for language
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 0.0.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

class translate
{
    /**
     * @var array[] translation text : 1st jey is string, second key is lang
     */
    private $aTranslation=array(
        'fr'=>array(
            'Mails sent'=>'Nombre d\'envoi',
            'Daily participation'=>'Participation journalière',
            'Daily participation (cumulative)'=>'Participation journalière cumulée',
            'Participation rate'=>'Taux de participations',
            'Total responses: %s'=>'Nombre total : %s',
            'Total Population'=>'Population Totale',
            'Population'=>'Population',
        ),
    );
    /**
     * Quick translate function
     */
    public function gT($string)
    {
        if(isset($this->aTranslation[App()->language][$string])){
            return $this->aTranslation[App()->language][$string];
        }
        return gT($string);
    }
}

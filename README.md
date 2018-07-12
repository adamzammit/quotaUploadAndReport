# quickStatAdminParticipationAndStat

A plugin for limesurvey to show some quick statistics about participation and satisfaction.

## Installation

See [Install and activate a plugin for LimeSurvey](https://extensions.sondages.pro/install-and-activate-a-plugin-for-limesurvey) for details.

### Via GIT
- Go to your LimeSurvey Directory
- Clone in plugins/adminStats directory : `git clone https://framagit.org/SondagePro-LimeSurvey-plugin/quickStatAdminParticipationAndStat.git quickStatAdminParticipationAndStat`

### Via ZIP dowload
- Download <http://extensions.sondages.pro/IMG/auto/quickStatAdminParticipationAndStat.zip>
- Extract : `unzip quickStatAdminParticipationAndStat.zip`
- Move the directory to  plugins/ directory inside LimeSUrvey

## Usage

Global settings offer what survey admin can choose for particpation.

In Survey settings you can choose the question to show for participation and question for satisfaction.

- Particpation question are single choice questions or token attribute (if exist)
- Satisfaction question can be single choice, numeric or array question type. The mean is calculated with numerical code of answers only, tyhis allow to use Not Applicable option for example.

If the an user don't have global permission except login : it was redirected to a survey list. Only survey with statictics Permission are shown.

## Home page & Copyright
- HomePage <https://extensions.sondages.pro/export-statistics-and-database/quick-statistics-panel-participation-and-satisfaction/>
- Copyright © 2016-2018 Denis Chenu <http://sondages.pro>
- Copyright © 2016 Advantage <http://www.advantage.fr>
- Licence : GNU Affero General Public License <https://www.gnu.org/licenses/agpl-3.0.html>

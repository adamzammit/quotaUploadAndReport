# adminStats

A plugin for limesurvey to show some quick statistics about participation and satisfaction.

## Installation

### Via GIT
- Go to your LimeSurvey Directory
- Clone in plugins/adminStats directory : `git clone https://git.framasoft.org/SondagePro-LimeSurvey-plugin/adminStats.git adminStats`

## Usage

In Survey settings you can choose the question to show for participation and question for satisfaction.

- Particpation question are single choice questions or token attribute (if exist)
- Satisfaction question can be single choice, numeric or array question type. For non-numerical questions the mean is calculated with numerical code of answers only.

If the an user have only access to statistics : after login it was redirected to a survey list. Only survey with statictics Permission are shown.


## Home page & Copyright
- HomePage <http://extensions.sondages.pro/>
- Copyright © 2016-2017 Denis Chenu <http://sondages.pro>
- Copyright © 2016 Advantage <http://www.advantage.fr>
- Licence : GNU Affero General Public License <https://www.gnu.org/licenses/agpl-3.0.html>

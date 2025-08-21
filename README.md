# quotaUploadAndReport

A plugin for LimeSurvey that shows a summary of participation over time, the ability to bulk upload quotas, and reporting on those quotas

## Installation

Supports LimeSurvey 6.x

### Via GIT
- Go to your LimeSurvey Directory
- Clone in plugins/quotaUploadAndReport directory : `git clone https://github.com/adamzammit/quotaUploadAndReport`

## Usage

### Global Settings

- Choose if you want to automatically redirect users with only the permission to view statistics to a summary page which links to this plugin
- Choose "Activate daily participation" if you want surveys to be able to enable daily participation reports
- Choose "Activate daily action" if you want surveys to be able to enable daily activity reports

### Survey settings

In your survey settings menu, choose "Simple plugins" then "Settings for plugin quotaUploadAndReport"

"Link to statistics" brings up the report screen.
"Manage statistics" brings up the settings screen.

Settings:

- Alternate title: A new name for the report (otherwise the survey name)
- Expected participation: Your expected number of completions, used to calculate response rate
- Description for participation tab: Any text you want to appear in the participation report
- Show the number of completed daily responses: Enable or disable this graph
- Show the number of completed daily cumulative responses: Enable or disable this graph
- Show the number of daily survey opens: Enable or disable this graph
- Show the number of daily survey actions (clicked at least once): Enable or disable this graph
- Questions to show frequency tables in report: Choose questions from your survey to include a summary report (frequency table)
- Create index on token table/response table: If you notice the reports are running slowly, enable the index function which may speed up reporting

"Upload a new quota to the survey" allows for uploading a set of quotas via CSV file. The CSV file must be formatted in a particular way. Here are the details:

- The filename is used as a unique identifier. If you have already uploaded a quota file with the same name, you will receive an error
- Must contain a header row
- The column header must match the code for the question to create a quota for
- If multiple columns are entered, then multi-level quotas will be generated
- If a column is called "quota" this will be used as the total completes for that quota. If the value is blank or the column doesn't exist, the quota will be created but set as disabled. Note setting to 0 will mean anyone who chooses the matching response will be quota-ed out
- If a column is called "message", and it isn't blank, this will be used as the message to display to the respondent when the quota is reached
- If a column is called "url", and it isn't blank this will be used as the URL to automatically send respondents to when the quota is reached (overrides message)


## Contributing and issues

All contributions are welcome. Please use: https://github.com/adamzammit/quotaUploadAndReport


## Home page and Copyright
- HomePage <https://www.acspri.org.au/limesurvey>
- Copyright © 2025 ACSPRI <https://www.acspri.org.au>
- Copyright © 2016-2023 Denis Chenu <http://sondages.pro>
- Copyright © 2016-2023 Advantage <http://www.advantage.fr>
- Licence : GNU Affero General Public License <https://www.gnu.org/licenses/agpl-3.0.html>

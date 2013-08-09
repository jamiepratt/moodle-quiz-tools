Some rough tools related to the quiz module.
===========================================

These tools are pretty rough but functional in Moodle 2.6.

## Script to generate random attempt data for questions in a quiz

The script in responsegenerator/ can be put pretty much anywhere on a web server. You should just have to edit the line that
includes config.php in generatenewresponses.php then point your browser at the generatenewresponses.php script to run it.
* You can use a 'path' GET parameter to point the script at questionsXX.php which tells the script about the questions in the
quiz. Then the script will calculate some random student response data and the grades expected for that response data. The script
defaults to looking for questionsXX.php and saving stepsXX.php and resultsXX.php in the same directory as the script.
* You must use a num GET parameter  to tell the script what XX is. 'num' is then zero padded to give a two digit integer.
* You can edit line 25 of generatenewresponses.php to specify how many student attempts you want to generate random data for.

## stats.xls and stats.ods stats spreadsheet in statsspreadsheet/

stats.xls and stats.ods in statsspreadsheet/ are spreadsheets used for calculating a quiz's stats. Used primarily to have a
method of comparing stats for comparison to the same stats which are calculated by the statistics report.

This spreadsheet was used to calculate the stats as used in /mod/quiz/report/statistics/tests/stats_from_steps_walkthrough_test.php


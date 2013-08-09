<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Takes a param XX and looks for the file questionsXX.csv. It will then generate
 * a file stepsXX.csv and resultsXX.csv with random student responses for the quiz.
 *
 * @package    mod_quiz
 * @category   phpunit
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

// Make sure this points to config.php.
require_once(dirname(__FILE__) . '/../../../../config.php');

require_once(dirname(__FILE__).'/generatenewresponseslib.php');

$num = required_param('num', PARAM_INT);
$path = optional_param('path', '', PARAM_SAFEPATH);

if (empty($path)) {
    $path = dirname(__FILE__);
} else {
    $path = $CFG->dirroot."$path";
}

$num = sprintf("%02d", $num);

$questionsfilename = $path."/questions$num.csv";
$stepsfilename = $path."/steps$num.csv";
$resultsfilename = $path."/results$num.csv";

if (!is_readable($questionsfilename)) {
    throw new coding_exception("File 'questions$num.csv' not found.");
} else if (is_readable($stepsfilename)) {
    //throw new coding_exception("File 'steps$num.csv' already exists.");
} else if (is_readable($resultsfilename)) {
    //throw new coding_exception("File 'results$num.csv' already exists.");
}

if (!$fh = fopen($questionsfilename, 'r')) {
    throw new coding_exception("Cannot open filename '".basename($questionsfilename)."' to read question data.");
}
$columns = fgetcsv($fh);

$qsbyslot = array();
$qsbycat = array();
$stepscolumns = array('quizattempt', 'firstname', 'lastname');

while (($row = fgetcsv($fh)) !== false) {
    $q = explode_dot_separated_keys_to_make_subindexs(array_combine($columns, $row));
    $slotno = $q['slot'];
    $qtype = $q['type'];
    if (!empty($slotno)) {
        $qsbyslot[$slotno] = $q;
        if ($qtype == 'random') {
            $stepscolumns[] = "randqs.$slotno";
        }
        if ($qtype == 'calculatedsimple') {
            $stepscolumns[] = "variants.$slotno";
        }
        $rks = get_response_keys($qtype, $q['which']);
        foreach ($rks as $rk) {
            $stepscolumns[] = "responses.$slotno.$rk";
        }
    } else {
        if (!isset($qsbycat[$q['cat']])) {
            $qsbycat[$q['cat']] = array();
        }
        $qsbycat[$q['cat']][] = $q;
    }
}

var_dump(compact('qsbyslot','qsbycat','stepscolumns'));

if (!$resultsfh = fopen($resultsfilename, 'w')) {
    throw new coding_exception("Cannot open filename '".basename($resultsfilename)."' to write results data.");
}

$resultcolumns = array('quizattempt');
foreach (array_keys($qsbyslot) as $slotno) {
    $resultcolumns[] = "slots.$slotno.mark";
}
$resultcolumns[] = 'summarks';
fputcsv($resultsfh, $resultcolumns);

if (!$stepsfh = fopen($stepsfilename, 'w')) {
    throw new coding_exception("Cannot open filename '".basename($stepsfilename)."' to write steps data.");
}


echo '<pre>';
echo(join(',', $stepscolumns)."\n");
fputcsv($stepsfh, $stepscolumns);
$quizattempt = 1;
foreach (array('John', 'Joe', 'Roberto', 'Timothy', 'Bob', 'Aki', 'Sako', 'Einar', 'Elias', 'Marcus', 'Arnold',
             'Al', 'Lee', 'Richard', 'Zoe', 'David', 'Jamie', 'Jim', 'Jeff', 'Malachy', 'Michael') as $firstname) {
    foreach (array('Jones', 'Smith', 'Vicars', 'Pacino', 'Deniro', 'Banks', 'Asimov', 'Chomsky', 'Yamaguchi',
                 'Robbins') as $lastname) {
        $rowmarksum = 0;
        $steprow = array($quizattempt, $firstname, $lastname);
        $resultrow = array($quizattempt);
        foreach ($qsbyslot as $q) {
            list($steprowcells, $mark) = get_step_data($q, $qsbycat);
            $rowmarksum += $mark;
            $resultrow[] = round($mark, 7);
            $steprow = array_merge($steprow, $steprowcells);
        }
        fputcsv($stepsfh, $steprow);
        echo(join(',', $steprow)."\n");
        $resultrow[] = round($rowmarksum, 5);
        fputcsv($resultsfh, $resultrow);
        if ($quizattempt == 25) {
            break 2;
        }
        $quizattempt++;
    }
}
echo '</pre>';

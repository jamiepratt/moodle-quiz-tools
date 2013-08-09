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

function explode_dot_separated_keys_to_make_subindexs(array $row) {
    $parts = array();
    foreach ($row as $columnkey => $value) {
        $newkeys = explode('.', trim($columnkey));
        $placetoputvalue =& $parts;
        foreach ($newkeys as $newkeydepth => $newkey) {
            if ($newkeydepth + 1 === count($newkeys)) {
                $placetoputvalue[$newkey] = $value;
            } else {
                // Going deeper down.
                if (!isset($placetoputvalue[$newkey])) {
                    $placetoputvalue[$newkey] = array();
                }
                $placetoputvalue =& $placetoputvalue[$newkey];
            }
        }
    }
    return $parts;
}

function get_response_keys($qtype, $which) {
    if ($qtype === 'match') {
        return array(0, 1, 2);
    } else if ($qtype === 'multianswer') {
        return array('1.answer', '2.answer');
    } else if ($qtype === 'multichoice' && $which === 'two_of_four') {
        return array(0, 1, 2, 3);
    } else {
        return array('answer');
    }
}
function pick_random_answer($answers, $weighting, $marks) {
    $randomnum = rand(0, array_sum($weighting));
    $sum = 0;
    $ansindex = 0;
    while ($randomnum > ($sum + $weighting[$ansindex])) {
        $sum += $weighting[$ansindex];
        $ansindex++;
    }
    if (is_array($answers[$ansindex])) {
        $toreturn =  $answers[$ansindex];
    } else {
        $toreturn =  array($answers[$ansindex]);
    }
    return array($toreturn, $marks[$ansindex]);
}

/**
 * @param $q        array question data from csv file.
 * @param $qsbycat  array of arrays of question data from csv file.
 * @return array with contents 0 => responses, 1 => mark
 */
function get_step_data($q, $qsbycat) {
    $functionname = "qtype_{$q['type']}_get_step_data";
    return $functionname($q['which'], $q, $qsbycat);
}


function qtype_random_get_step_data($which, $q, $qsbycat) {
    $randomquestions = $qsbycat[$q['cat']];
    $randindex = rand(0, count($randomquestions) - 1);
    $randomquestion = $randomquestions[$randindex];
    $randomquestionname = $randomquestion['type'];
    if (!empty($randomquestion['which'])) {
        $randomquestionname .= ('_'.$randomquestion['which']);
    }
    $data = array($randomquestionname);
    list($answer, $fraction) = get_step_data($randomquestion, $qsbycat);
    return array(array_merge($data, $answer), $fraction);
}
function qtype_shortanswer_get_step_data($which, $q, $qsbycat) {
    return pick_random_answer(array('frog', 'toad', 'tadpole', 'butterfly', random_string(rand(5, 15))),
                                              array(70, 35, 10, 5, 5),
                                              array(1.0, 0.8, 0, 0, 0));
}

function qtype_numerical_get_step_data($which, $q, $qsbycat) {
    return pick_random_answer(array('3.14', '3.1', '3.142', '3', rand(20,40) / 10),
                                array(70, 35, 10, 5, 5),
                                array(1.0, 0, 0, 0, 0));
}

function qtype_calculatedsimple_get_step_data($which, $q, $qsbycat) {
    return pick_random_answer(array(array(1, 9.9),
                                  array(2, 8.5),
                                  array(3, 3.3),
                                  array(4, 19.4),
                                  array(5, 14.2),
                                  array(6, 9.4),
                                  array(7, 9.1),
                                  array(8, 5.7),
                                  array(9, 7.1),
                                  array(10, 6.8),
                                  array(rand(1, 10), rand(-10, 0) / 10)),
                             array(15, 15, 15, 15, 15,
                                  15, 15, 15, 15, 15,
                                  150),
                             array(1, 1, 1, 1, 1,
                                 1, 1, 1, 1, 1,
                                 0));
}

function shuffle_correct_answer($correctanswer) {
    $wronganswer = $correctanswer;
    shuffle($wronganswer);
    $marks = 0;
    foreach ($wronganswer as $key => $value) {
        if ($correctanswer[$key] == $value) {
            $marks++;
        }
    }
    return array($wronganswer, $marks / count($wronganswer));
}

function qtype_match_get_step_data($which, $q, $qsbycat) {
    $correctanswer = array('amphibian', 'mammal', 'amphibian');
    list($incorrectanswer, $wrongmark) = shuffle_correct_answer($correctanswer);
    return pick_random_answer(array($correctanswer, $incorrectanswer), array(70, 30), array(1, $wrongmark));
}

function qtype_truefalse_get_step_data($which, $q, $qsbycat) {
    return pick_random_answer(array(1, 0), array(70, 30), array(1, 0));
}

function qtype_multianswer_get_step_data($which, $q, $qsbycat) {
    list($answer1, $mark1) = pick_random_answer(array('Dog', 'Owl', 'Pussy-cat', 'Wiggly worm', random_string(rand(3, 8))),
                                                array(20, 70, 10, 10, 10),
                                                array(0, 1, 0, 0, 0));
    list($answer2, $mark2) = pick_random_answer(array(0, 1, 2), array(20, 10, 70), array(0, 0, 1));
    return array(array_merge($answer1, $answer2), ($mark1 + $mark2) /2);
}

function qtype_multichoice_get_step_data($which, $q, $qsbycat) {
    if ($which == 'one_of_four') {
        return pick_random_answer(array(0, 1, 2, 3), array(70, 20, 10, 10), array(1, 0, 0, 0));
    } else {
        return shuffle_correct_answer(array('1', '0', '1', '0'));
    }
}

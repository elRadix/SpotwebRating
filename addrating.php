<?php
// AddRating
//
// This script will add IMDB ratings to the spot title, like: "Movietitle (2014) release info [6.5]
// It will also adjust the spot rating, based on the IMDB rating
// The script assumes the spot title syntax is like: "Movie title (year) release info"

// Location of Spotweb db settings file
$dbsettings = "./httpdocs/dbsettings.inc.php";


// $debug = True   : will show a table in the browser with the results
// $debug = False  : will run with no output (quiet)
$debug = false;

// Age of spots to query for (in seconds):
$age = 86400; // 1 day

// Minimum string similarity between movie title from spot and movie title from IMDB (http://php.net/manual/en/function.similar-text.php)
// Percentage (100 is exact match):
$min_similar_text = 75;

// Create MySQL connection (fill in correct values):
require($dbsettings) or die("Could not load db settings file");
$con=mysqli_connect($dbserver,$dbusername,$dbuserpassword,$dbname);

// Initiate the IMDB class:
require("./imdb.php") or die("Could not load IMDB module");
$imdb = new Imdb();

// Check connection:
if (mysqli_connect_errno($con)) {
    if ($debug) echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
else {
    // Connection is ok
    $timestamp = time() - $age;
    // This query will return all x264 movies (category 0 is "images", subcatz z0 is "movies" and subcata a09 is "x264"):
    $query = "SELECT * FROM spots WHERE category=0 AND subcatz='z0|' AND subcata='a9|' AND stamp>" . $timestamp . " ORDER BY stamp";
    $result = mysqli_query($con,$query);
    if ($debug) echo "<table border='1'>";
    if ($debug) echo "<tr> <th>Title</th>  <th>Movie title from spot</th> <th>Year from spot</th> <th>IMDB title from IMDB</th><th>Year from IMDB</th><th>Similarity (min: " . $min_similar_text . "%)</th><th>Match?</th><th>Rating</th> </tr>";
    // Process all results:
    while ($row = mysqli_fetch_array($result)) {
        $title = $row['title'];
        if ($debug) echo "<tr>";
        if ($debug) echo "<td>" . $row['title'] . "</td>";

        // Regular expression to try to get a "clean" movietitle from the spot title (all text until "year"):
        if ((preg_match('/(.+)[ \(\.]((19|20)\d{2})/', $row['title'], $matches)) == 1) {
            $title_from_spot = trim($matches[1]);
            $year = trim($matches[2]);
            $title_from_spot = str_replace(".", " ", $title_from_spot);
            if ($debug) echo "<td>" . $title_from_spot . "</td>";
            if ($debug) echo "<td>" . $year . "</td>";
            // Search movie info from IMDB:
            $movieArray = $imdb->getMovieInfo($title_from_spot . " (" . $year . ")", False);
            if (isset($movieArray['title_id']) and !empty($movieArray['title_id'])) { //if search success
                $imdb_year = trim($movieArray['year']);
                $imdb_title = $movieArray['title'];
                $imdb_url = $movieArray['imdb_url'];
                // Calculate the similarity between the movietitle from the spot and the movietitle found in IMDB:
                similar_text(strtolower($title_from_spot), strtolower($imdb_title), $percent);
                if ($debug) echo "<td><a href=\"" . $imdb_url . "\">" . $imdb_title . "</a></td><td>" . $imdb_year . "</td><td>" . Round($percent, 2) . "%</td>";
                // Assume the correct movie is found in IMDB when the similarity is higher then defined and the year from IMDB is the same as from the spot:
                if (($imdb_year == $year) and ($percent >= $min_similar_text)) {
                    $imdb_rating = $movieArray['rating'];
                    // When an IMDB rating is found:
                    if (isset($movieArray['rating']) and !empty($movieArray['rating'])) {
                        if ($debug) echo "<td>yes</td><td>" . $imdb_rating . "</td>";
                        // If the rating had already been added to the title, strip it:
                        if ((preg_match('/(.+)( \[\d\.\d\])/', $title, $matches)) == 1) {
                            $title = $matches[1];
                        }
                        // Add the rating to the spot title:
                        $newtitle = $title . " [" . $imdb_rating . "]";
                        $updatequery = "UPDATE spots SET title = '" . $newtitle . "' WHERE id = " . $row['id'];
                        $updateresult = mysqli_query($con,$updatequery);
                        $spotrating = 0;
                        // Calculate the spotrating based on imdb rating (only valid spotrating when imdb rating is at least 6.0):
                        if ($imdb_rating >= 6.0) {$spotrating = 1;}
                        if ($imdb_rating >= 6.2) {$spotrating = 2;}
                        if ($imdb_rating >= 6.4) {$spotrating = 3;}
                        if ($imdb_rating >= 6.6) {$spotrating = 4;}
                        if ($imdb_rating >= 6.8) {$spotrating = 5;}
                        if ($imdb_rating >= 7.0) {$spotrating = 6;}
                        if ($imdb_rating >= 7.2) {$spotrating = 7;}
                        if ($imdb_rating >= 7.4) {$spotrating = 9;}
                        if ($imdb_rating >= 7.6) {$spotrating = 10;}
                        $updatequery = "UPDATE spots SET spotrating = '" . $spotrating . "' WHERE id = " . $row['id'];
                        $updateresult = mysqli_query($con,$updatequery);
                    }
                    // Clear spotrating if no rating found in IMDB
                    else {
                        $spotrating = 0;
                        $updatequery = "UPDATE spots SET spotrating = '" . $spotrating . "' WHERE id = " . $row['id'];
                        $updateresult = mysqli_query($con,$updatequery);
                        if ($debug) echo "<td>yes</td><td>n/a</td>";
                    }
                }
                // Clear spotrating if the correct movie is not found in IMDB
                else {
                    $spotrating = 0;
                    $updatequery = "UPDATE spots SET spotrating = '" . $spotrating . "' WHERE id = " . $row['id'];
                    $updateresult = mysqli_query($con,$updatequery);
                    if ($debug) echo "<td>no</td><td></td>";
                }
            }
            // Clear spotrating if no movie is found in IMDB
            else {
                $spotrating = 0;
                $updatequery = "UPDATE spots SET spotrating = '" . $spotrating . "' WHERE id = " . $row['id'];
                $updateresult = mysqli_query($con,$updatequery);
                if ($debug) echo "<td>NO IMDB MOVIE FOUND</td><td></td><td></td><td></td><td></td>";
            }
        }
        // Clear spotrating if the movie title could not be extracted from the spot title
        else {
                $spotrating = 0;
                $updatequery = "UPDATE spots SET spotrating = '" . $spotrating . "' WHERE id = " . $row['id'];
                $updateresult = mysqli_query($con,$updatequery);
                if ($debug) echo "<td>NO TITLE FOUND</td><td></td><td></td><td></td><td></td><td></td><td></td>";
        }
        if ($debug) echo "</tr>";
    }
    if ($debug) echo "</table>";
    // Close MySQL connection:
    mysqli_close($con);
}
?>

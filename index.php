<?php

    $licenseTypes = array(
        "none" => "None",
        "apache2" => "Apache License 2.0",
        "bsd3" => "BSD 3-Clause 'New' or 'Revised' license",
        "bsd2" => "BSD 2-Clause 'Simplified' or 'FreeBSD' license",
        "gpl2" => "GNU General Public License (GPL) Version 2.0",
        "gpl3" => "GNU General Public License (GPL) Version 3.0",
        "lgpl2" => "GNU Library or 'Lesser' General Public License (LGPL) Version 2.1",
        "lgpl3" => "GNU Library or 'Lesser' General Public License (LGPL) Version 3.0",
        "mit" => "MIT license"
    );

    $replacements = array();

    // Generate a list of folders in the template
    function gatherFolders($dir = "") {
        $folders = array();
        $fd = opendir("template/" . $dir);
        while ($file = readdir($fd)) {
            if (substr($file,0,1) == '.') {
                continue;
            }
            if ($dir != "") {
                $fn = $dir . "/" . $file;
            } else {
                $fn = $file;
            }
            if (is_dir("template/" . $fn)) {
                $folders[] = $fn;
                $descend = gatherFolders($fn);
                $folders = array_merge($folders, $descend);
            }
        }
        return $folders;
    }
   
    // Gather a list of files in the template folder
    function gatherFiles($dir = "") {
        $files = array();
        $fd = opendir("template/" . $dir);
        while ($file = readdir($fd)) {
            if (substr($file,0,1) == '.') {
                continue;
            }
            if ($dir != "") {
                $fn = $dir . "/" . $file;
            } else {
                $fn = $file;
            }
            if (is_dir("template/" . $fn)) {
                $descend = gatherFiles($fn);
                $files = array_merge($files, $descend);
            } else {
                $files[] = $fn;
            }
        }
        return $files;
    }

    // Generate a unique temporary file name for building the zip file
    function uniqueName() {
        $tmp = sys_get_temp_dir();
        $fn = $tmp . "/library_template_" . rand(100000, 999999) . ".zip";
        while(file_exists($fn)) {
            $fn = $tmp . "/library_template_" . rand(100000, 999999) . ".zip";
        }
        return $fn;
    }

    // Confirm that the name is a valid library name.
    function validName($n) {
        $n = strtoupper($n);
        $valid = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9','0','_');
        $start = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','_');
        $chars = str_split(strtoupper($n));

        // Does the library start with a valid starting character?
        if (!in_array($chars[0], $start)) {
            return false;
        }

        // Are there any invalid characters?
        foreach ($chars as $char) {
            if (!in_array($char, $valid)) {
                return false;
            }
        }

        // All tests have passed, so it must be valid.
        return true;
    }

    function stringReplacement($in) {
        global $replacements;
        $out = $in;
        foreach ($replacements as $k => $v) {
            $out = str_replace("%$k%", $v, $out);
        }
        return $out;
    }

    // Clean up a filename to remove bad characters
    function sanitize($in) {
        $out = $in;
        $bad = array('%','.','/','\\','&','?','*');
        foreach ($bad as $c) {
            $out = str_replace($c, '', $out);
        }
        return $out;
    }


    // This will store a string for displaying errors if any
    $error = false;

    // If we have been passed a library name then we must be generating the library.
    if (array_key_exists("libname", $_POST)) {
        $lib = trim($_POST['libname']);
        $libcap = strtoupper($lib);
        $license = trim($_POST['license']);
        $owner = trim($_POST['owner']);

        // Check the library name is valid, and report an error if not.
        if (!validName($lib)) {
            $error = "Name contains invalid characters.  Use only A-Z, a-z, 0-9 and _.  The name must not start with a number.";
        } else {

            // Just in case someone tries walking our filesystem, let's clean the license variable.
            $license = sanitize($license);

            $licenseData = "";
            // Load the license text from the right text file.
            if (file_exists("licenses/$license.txt")) {
                $licenseData = file_get_contents("licenses/$license.txt");
            }

            $replacements['OWNER'] = $owner;
            $replacements['LIBNAME'] = $lib;
            $replacements['LIBCAP'] = $libcap;
            $replacements['YEAR'] = $year;
            $replacements['LICENSE'] = stringReplacement($licenseData);

            $folders = gatherFolders();
            $files = gatherFiles();

            // And now we build it into a .ZIP file.
            $zip = new ZipArchive;
            $zn = uniqueName();
            $zip->open($zn, ZipArchive::CREATE);

            foreach ($folders as $f) {
                $f = stringReplacement($f);
                $zip->addEmptyDir($f);
            }

            foreach ($files as $f) {
                $data = file_get_contents("template/" . $f);
                $data = stringReplacement($data);
                $newfile = stringReplacement($f);
                $zip->addFromString($newfile, $data);
            }

            $zip->close();
        
            // This pushes the file out as an attachment - i.e., forces a download with a specific filename.
            header("Content-type: application/zip");
            header("Content-Transfer-Encoding: Binary"); 
            header("Content-disposition: attachment; filename=\"${lib}.zip\""); 

            // And send the file, and delete it.  We can terminate the script now as we're not wanting to do anything else.
            readfile($zn);
            unlink($zn);
            exit(0);
        }
    }

    // Everything else now is to do with displaying the web page.
?>
<html>
<head>
<link rel="stylesheet" href="main.css" />
<title>Automatic Library Template Generator</title>
</head>
<body style="background-color: white;">
<center>
<h1>Automatic Library Template Generator</h1>

<i>Copyright &copy; 2013 Majenko Technologies</i><br/><br/>

<b>This little utility will generate a blank set of library files for you to fill with your own
code.  A single class will be created for the name you provide containing a few fairly standard
member functions.</b><br/><br/>

<form method="POST" action="#">

<b>Enter Library Name: </b><input type="text" name="libname" size="20" /> <br/>

<b>Include license information: </b><select name="license">
<?php
    foreach ($licenseTypes as $file => $name) {
        print "<option value='$file'>$name</option>\n";
    }
?>
</select><br/>

<b>Your name (for the license): </b><input type="text" name="owner" size="20" /><br/>

<input type="submit" value="Generate"/>

</form>

<?php
    // If we have been given an error string, then display it here.
    if ($error !== false) {
?>
<span class="error"><?php print $error; ?></span>
<?php
    }
?>
</center>
</body>
</html>

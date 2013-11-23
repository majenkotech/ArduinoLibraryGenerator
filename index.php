<?php

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

    // This will store a string for displaying errors if any
    $error = false;

    // If we have been passed a library name then we must be generating the library.
    if (array_key_exists("libname", $_POST)) {
        $lib = $_POST['libname'];
        $lib = trim($lib);
        $libcap = strtoupper($lib);
        $license = $_POST['license'];
        $owner = $_POST['owner'];

        // Just in case someone tries walking our filesystem, let's clean the license variable.
        $license = str_replace("%", "", $license);
        $license = str_replace(".", "", $license);
        $license = str_replace("/", "", $license);
        $license = str_replace("\\", "", $license);
        $license = str_replace("&", "", $license);
        $license = str_replace("?", "", $license);
        $license = str_replace("*", "", $license);

        // Load the license text from the right text file.
        $licenseData = file_get_contents("licenses/$license.txt");
        $licenseData = str_replace("%OWNER%", $owner, $licenseData);
        $licenseData = str_replace("%YEAR%", date("Y"), $licenseData);
        $licenseData = str_replace("%LIBRARY%", $lib, $licenseData);

        // Check the library name is valid, and report an error if not.
        if (!validName($lib)) {
            $error = "Name contains invalid characters.  Use only A-Z, a-z, 0-9 and _.  The name must not start with a number.";
        } else {

            // This is the template header file.  It might be better to have this in a file loaded from the FS, but for
            // now it's static in the code.
            $header = $licenseData . "#ifndef _${libcap}_H
#define _${libcap}_H

#if (ARDUINO >= 100) 
# include <Arduino.h>
#else
# include <WProgram.h>
#endif

class ${lib} {
    private:
        // Private functions and variables here.  They can only be accessed
        // by functions within the class.

    public:
        // Public functions and variables.  These can be accessed from
        // outside the class.
        ${lib}();
        void begin();
};
#endif
";

        // Ditto with the .cpp file.
        $code = $licenseData . "#include <${lib}.h>

// This is a generic constructor.  Expand as needed.  Constructors
// don't have a return type.
${lib}::${lib}() {
}

// Initialize any hardware here, not in the constructor.  You cannot
// guarantee the execution order of constructors, but you can guarantee
// when the begin member function is executed.
void ${lib}::begin() {
}
";

            // And now we build it into a .ZIP file.
            $zip = new ZipArchive;
            $zn = uniqueName();
            $zip->open($zn, ZipArchive::CREATE);
            $zip->addEmptyDir($lib);
            $zip->addEmptyDir("$lib/examples");
            $zip->addEmptyDir("$lib/utility");
            $zip->addFromString("$lib/$lib.h", $header);
            $zip->addFromString("$lib/$lib.cpp", $code);
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

<option selected value="none">None</option>
<option value="apache2">Apache License 2.0</option>
<option value="bsd3">BSD 3-Clause "New" or "Revised" license</option>
<option value="bsd2">BSD 2-Clause "Simplified" or "FreeBSD" license</option>
<option value="gpl2">GNU General Public License (GPL) Version 2.0</option>
<option value="gpl3">GNU General Public License (GPL) Version 3.0</option>
<option value="lgpl2">GNU Library or "Lesser" General Public License (LGPL) Version 2.1</option>
<option value="lgpl3">GNU Library or "Lesser" General Public License (LGPL) Version 3.0</option>
<option value="mit">MIT license</option>

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

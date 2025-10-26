<?php
// exec('pdf2htmlEX --first-page 1 --last-page 2 PA31.pdf --dest-dir uploads/ 2>&1',$output);
exec('pdf2htmlEX  PA31.pdf --dest-dir uploads/ 2>&1',$output, $retval);
print_r($output);
print_r($retval);
<?php

function fragmenter($texte)
{
    
    $separateurs = " ,.;'’(«»):!?–€"; 
    
    $tok = strtok($texte, $separateurs);
    $tab_tok = array(); 

    
    while ($tok !== false) 
    {
        if (strlen($tok) > 2) {
            $tab_tok[] = $tok;
        }
        
        $tok = strtok($separateurs);
    }
    
    return $tab_tok;
}
?>
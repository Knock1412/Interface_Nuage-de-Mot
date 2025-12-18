<?php

include 'includes/indexation.php'; 

$json_data = "[]"; 
$message_erreur = "";
$show_cloud = false; 

// Fonction pour s'assurer que toutes les chaînes sont en UTF-8
function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string($d)) {
        return mb_convert_encoding($d, 'UTF-8', 'UTF-8');
    }
    return $d;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $texte_a_traiter = isset($_POST['texte_utilisateur']) ? $_POST['texte_utilisateur'] : "";
    
    if (!empty($texte_a_traiter)) {
        // 1. Normalisation
        $texte_a_traiter = mb_convert_encoding($texte_a_traiter, 'UTF-8', 'auto');
        $texte_a_traiter = mb_strtolower($texte_a_traiter, 'UTF-8');
        
        // 2. Découpage
        $tab_mots = fragmenter($texte_a_traiter);

         // 3. Filtrage des Mots-Vides
        if (file_exists('data/mots-vides.txt')) {
            
            $mots_vides = file('data/mots-vides.txt');
            
            $mots_vides = array_map('trim', $mots_vides);
            
            $tab_mots = array_diff($tab_mots, $mots_vides);
        }

        // 4. Comptage et Tri
        $tab_poids = array_count_values($tab_mots);
        arsort($tab_poids);
        $tab_poids = array_slice($tab_poids, 0, 150); 

        // 5. Préparation JSON
        $final_array = [];
        foreach($tab_poids as $mot => $freq) {
            
            $mot_propre = mb_convert_encoding($mot, 'UTF-8', 'UTF-8');
            $final_array[] = [$mot_propre, $freq];
        }

        
        $json_data = json_encode($final_array);

        // Gestion d'erreur
        if (json_last_error() !== JSON_ERROR_NONE) {
            
            $json_data = json_encode($final_array, JSON_INVALID_UTF8_IGNORE);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                 $message_erreur = "Erreur fatale JSON : " . json_last_error_msg();
            } else {
                 $show_cloud = true;
            }
        } else {
            $show_cloud = true;
        }

    } else {
        $message_erreur = "Le champ texte est vide !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordCloud</title>
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/wordcloud2.js"></script>
</head>
<body>

  <header>
    <div class="container">
        <div class="logo">
            <i class="fa-solid fa-cloud" style="color:#e75e8d; margin-right:10px;"></i>
            NUAGE <span>DE MOT</span>
        </div>
    </div>
  </header>

  <div class="container">
    <div class="row">
      <div class="col-lg-12">
        <div class="page-content">

          <div class="heading-section" style="text-align:center; margin-bottom:40px;">
            <h4><em>Générez Votre Nuage</em></h4>
          </div>

          <div class="input-card">
             <form method="POST" action="">
                <textarea name="texte_utilisateur" rows="8" placeholder="Tapez ou collez votre texte ici..."><?php if(isset($_POST['texte_utilisateur'])) echo htmlspecialchars($_POST['texte_utilisateur']); ?></textarea>
                
                <div style="margin-top: 20px; text-align: right;">
                    <div class="main-button">
                        <button type="submit">Lancer l'Analyse</button>
                    </div>
                </div>
             </form>
          </div>

          <?php if($show_cloud || $message_erreur): ?>
              <div class="result-card">
                
                <div class="heading-section">
                    <h4><em>Résultat Du Traitement</em> </h4>
                </div>

                <?php if($message_erreur): ?>
                    <div class="alert-box">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $message_erreur; ?>
                    </div>
                <?php endif; ?>

                <?php if($show_cloud): ?>
                    <div id="cloud-container" class="cloud-container">
                        <canvas id="myCanvas"></canvas>
                    </div>

                    <div class="btn-download">
                        <button onclick="downloadCanvas()">
                            <i class="fa-solid fa-download"></i> Télécharger l'image
                        </button>
                    </div>
                <?php endif; ?>

              </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
  
  <script>
    const listeMots = <?php echo $json_data; ?>;
    const doitDessiner = <?php echo $show_cloud ? 'true' : 'false'; ?>;

    if (doitDessiner && listeMots.length > 0) {
        
        const canvas = document.getElementById('myCanvas');
        const container = document.getElementById('cloud-container');

        canvas.width = container.offsetWidth; 
        canvas.height = 550;

        const maxFreq = listeMots[0][1]; 
        const facteurZoom = 100 / maxFreq; 

        WordCloud(canvas, {
            list: listeMots,
            gridSize: 8,
            weightFactor: function (size) {
                return (size * facteurZoom) + 10; 
            },
            fontFamily: 'Poppins, sans-serif',
            color: 'random-dark', 
            backgroundColor: '#ffffff',
            rotateRatio: 0.5,
            shrinkToFit: true,
            drawOutOfBound: false
        });
    }

    function downloadCanvas() {
        const canvas = document.getElementById("myCanvas");
        const imageURL = canvas.toDataURL("image/png");
        const link = document.createElement('a');
        link.download = "mon-nuage.png";
        link.href = imageURL;
        link.click();
    }
  </script>

</body>
</html>
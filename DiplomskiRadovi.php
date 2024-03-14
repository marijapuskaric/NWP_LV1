<?php
    //ukljucivanje parsera za html
    include('simplehtmldom_1_9_1/simple_html_dom.php');

    //definicija sucelja
    interface iRadovi
    {
        public function create($data);
        public function save();
        public function read();
    }

    //definicija klase koja implementira sucelje Radovi
    class DiplomskiRadovi implements iRadovi
    {
        private $_id = NULL;
        private $_naziv_rada = NULL;
        private $_tekst_rada = NULL;
        private $_link_rada = NULL;
        private $_oib_tvrtke = NULL;

        function create($data)
        {
            $this->_naziv_rada = $data['naziv_rada'];
            $this->_tekst_rada = $data['tekst_rada'];
            $this->_link_rada = $data['link_rada'];
            $this->_oib_tvrtke = $data['oib_tvrtke'];
        }

        //funkcija za spremanje podataka u bazu, spaja se na bazu radovi 
        //te pomocu sql naredbe sprema naziv, tekst i link rada te oib tvrtke
        //id se postavlja automatski u bazi pomocu sql naredbe
        //ALTER TABLE `diplomski_radovi` CHANGE `ID` `ID` INT(12) NOT NULL AUTO_INCREMENT PRIMARY KEY;
        function save()
        {
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "radovi";

            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $sql = "INSERT INTO diplomski_radovi (naziv_rada, tekst_rada, link_rada, oib_tvrtke)
                    VALUES ('$this->_naziv_rada', '$this->_tekst_rada', '$this->_link_rada', '$this->_oib_tvrtke')";
            if ($conn->query($sql) === TRUE) {
            } else {
                echo "Error: " . $sql . "<br>" . $conn->error;
            }
            $conn->close();
        }

        //funkcija za citanje podataka iz baze, spaja se na bazu radovi
        //te pomocu sql naredbe dohvaca sve podatke iz tablice diplomski_radovi i ispisuje ih
        function read()
        {
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "radovi";

            $conn = new mysqli($servername, $username, $password, $dbname);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $sql = "SELECT ID, naziv_rada, tekst_rada, link_rada, oib_tvrtke FROM diplomski_radovi";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "ID: ". $row["ID"]. "<h3> Naziv rada: " . $row["naziv_rada"]. "</h3><br/>  Tekst rada: " 
                    . $row["tekst_rada"]. "<br/> Link rada: <a href=" . $row["link_rada"]. ">". $row["link_rada"].
                    "</a><br/> Oib tvrtke: " . $row["oib_tvrtke"]. "<br/><br/>";
                }
            } else {
                echo "0 results";
            }
            $conn->close();
        }  
    }

    //povezivanje na 4. stranicu pomocu cURL 
    $url = 'https://stup.ferit.hr/index.php/zavrsni-radovi/page/4';

    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_FAILONERROR, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);
    curl_close($curl);

    //parsiranje rezultata spajanja kako bi se dohvatili podatci za svaki clanak na stranici
    $dom = new simple_html_dom();
    $dom->load($result);

    foreach($dom->find('article') as $article)
    {
        foreach($article->find('h2.entry-title a') as $link)
        {
            //otvaranje svakog clanka kako bi se dohvatio tekst rada
            $curlArticle = curl_init($link->href);

            curl_setopt($curlArticle, CURLOPT_FAILONERROR, 1);
            curl_setopt($curlArticle, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curlArticle, CURLOPT_RETURNTRANSFER, 1);

            $html = curl_exec($curlArticle);
            curl_close($curlArticle);

            $domArticle = new simple_html_dom();
            $domArticle->load($html);

            foreach($domArticle->find('.post-content') as $text){}
        }
        foreach($article->find('img') as $image){}

       $rad = array(
            'naziv_rada' => $link->plaintext,
            'tekst_rada' => $text->plaintext,
            'link_rada' => $link->href,
            'oib_tvrtke' => preg_replace('/[^0-9]/', '', $image->src)
        ); 
        //stvaranje novog objekta DiplomskiRadovi
        $diplomski_rad = new DiplomskiRadovi();
        $diplomski_rad->create($rad);
        //poziv funkcije za spremanje diplomskog rada u bazu
        $diplomski_rad->save();
    }
    //poziv funkcije za ispis svih radova iz baze
    $diplomski_rad->read();
?>
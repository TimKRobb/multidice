<?php

interface MultiDiceInterface
{
    // cette méthode construit le widget. Elle devra faire appel au constructeur de la classe parent et accrochera au crochet wp_head le css défini dans la fonction css 
    // afin de pouvoir lister les 500 derniers jets j'ai décidé de créer un shortcode défini par la fonction liste_jets et accroché avec le shortcode liste_jets grâce à la fonction add_shortcode
    public function __construct();

    // la fonction form permet de créer le formulaire de paramétrage du widget
    // ici j'ai 2 paramètres : le nom du widget et la liste des dés que l'on peut lancer
	public function form($instance);

	// Fonction définissant un peu de css
	public function css(); 

	// Cette fonction contient le corps du module, c'est en appelant cette méthode qu'on affiche le widget
    public function widget($args, $instance);

    // Cette methode me permet de créer la table qui stockera tous les jets réalisés
    public static function install();

    // Cette méthode supprime la table de stockage des jets
	public static function uninstall();

	// Cette fonction me permet de renvoyer un tableau contenant $nb nombres compris entre 0 et $max
	public static function alea($max, $nb);

	// C'est dans cette fonction que je détermine le comportement de mon shortcode
	public static function liste_jets($atts, $content);

	// C'est dans cette methode que je vais stocker les éléments des jets
    public static function traitement();
}

class MultiDiceWidget extends WP_Widget implements MultiDiceInterface
{

	public function __construct() {
		parent::__construct(
			'MultiDiceWidget',
			__('MultiDice Widget', 'multidice_widget_domain'),
			array('description' => __( 'Roll multiple dice for RPGs', 'multidice_widget_domain' )),
			array()
		);
	}

    public function widget($args, $instance) {

    	$title = apply_filters( 'widget_title', $instance['title'] );
    	$output = "";
 
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) )
		echo $args['before_title'] . $title . $args['after_title'];
 
		// This is where you run the code and display the output

		if ( isset($_POST['multidiceroll']) ) {
			$output .= '<p class="multidiceresult">Résultat : ';
			$output .= $this->getLastResult();
			$output .= '</p>';
		}

		$output .= '<form id="mdwidget" method="post">';
		$output .= '<label for="mdd4">d4</label>';
		$output .= '<input type="number" min="0" name="mdd4" size="1" value="0">';
		$output .= '<label for="mdd6">d6</label>';
		$output .= '<input type="number" min="0" name="mdd6" size="1" value="0">';
		$output .= '<label for="mdd8">d8</label>';
		$output .= '<input type="number" min="0" name="mdd8" size="1" value="0">';
		$output .= '<label for="mdd10">d10</label>';
		$output .= '<input type="number" min="0" name="mdd10" size="1" value="0">';
		$output .= '<label for="mdd12">d12</label>';
		$output .= '<input type="number" min="0" name="mdd12" size="1" value="0">';
		$output .= '<label for="mdd100">d100</label>';
		$output .= '<input type="number" min="0" name="mdd100" size="1" value="0">';
		$output .= '<label for="offset">Offset</label>';
		$output .= '<input type="number" min="0" name="offset" size="1" value="0">';
		$output .= '<input type="submit" name="multidiceroll" value="ROLL!!">';
		$output .= '</form>';
		echo $output;

		echo $args['after_widget'];

    }

	public function form($instance) {

		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		} else {
			$title = __( 'New title', 'multidice_widget_domain' );
		}

		if ( isset( $instance[ 'd4' ] ) ) {
			$d4 = $instance[ 'd4' ];
		} else {
			$d4 = 'checked';
		}

		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['d4'] = ( ! empty( $new_instance['d4'] ) ) ? strip_tags( $new_instance['d4'] ) : '';
		return $instance;
	}

	public function css() {}

    public static function install() {

    	if ( ! self::DBTableExists() ) {
    		self::deleteDBTable();
    	}

    	self::createDBTable();

   }

	public static function uninstall() {
		self::deleteDBTable();
	}

	public static function alea($max, $nb) {}

	public static function liste_jets($atts, $content) {

		$output = "<table>";
		$output .= "<tr><th>Id</th><th>Time</th><th>Jet</th><th>UserId</th></tr>";

		global $wpdb;
		$sql = "SELECT * FROM `wp_jet` WHERE `userid` = " . get_current_user_id() . " ORDER BY `date` DESC LIMIT 500;";
		$results = $wpdb->get_results($sql);

		foreach ( $results as $line ) {
			$output .= "<tr>";
			$output .= "<td>" . $line->id . "</td>";
			$output .= "<td>" . $line->date . "</td>";
			$output .= "<td>" . $line->jet . "</td>";
			$output .= "<td>" . $line->userid . "</td>";
			$output .= "</tr>";
		}

		$output .= "</table>";

		return $output;

	}

    public static function traitement() {

    	if ( isset($_POST['multidiceroll']) ) {

    		$d4 = intval($_POST['mdd4']);
	   		$d6 = intval($_POST['mdd6']);
	   		$d8 = intval($_POST['mdd8']);
	   		$d10 = intval($_POST['mdd10']);
	   		$d12 = intval($_POST['mdd12']);
	   		$d100 = intval($_POST['mdd100']);
	   		$offset = intval($_POST['offset']);
	   		$nbDice = $d4+$d6+$d8+$d10+$d12+$d100;

	   		if ($nbDice>0) {
	   			$set = new DiceSet($offset);
	   			$i = 0; while ($i < $d4) { $set->addDice(4); $i++; }
	   			$i = 0; while ($i < $d6) { $set->addDice(6); $i++; }
	   			$i = 0; while ($i < $d8) { $set->addDice(8); $i++; }
	   			$i = 0; while ($i < $d10) { $set->addDice(10); $i++; }
	   			$i = 0; while ($i < $d12) { $set->addDice(12); $i++; }
	   			$i = 0; while ($i < $d100) { $set->addDice(100); $i++; }
	   			$set->roll();
	   		}

    	}

    }

    public function createDBTable() {
		global $wpdb;
		$sql = "CREATE TABLE `wp_jet` (
				`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`jet` text COLLATE utf8mb4_unicode_ci NOT NULL,
				`userid` int(11) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
		$wpdb->query($sql);
    }

    public function DBTableExists() {
    	global $wpdb;
    	$sql = "SHOW TABLES LIKE 'wp_jet';";
 		$results = $wpdb->get_results($sql);
 		return ! empty($results);
 	}

 	public function deleteDBTable() {
		global $wpdb;
		$sql = "DROP TABLE `wp_jet`;";
		$wpdb->query($sql);
 	}

 	private function getLastResult() {
     	global $wpdb;
    	$sql = "SELECT `jet` FROM `wp_jet`
    		WHERE `userid` = " . get_current_user_id() . "
    		ORDER BY `date` DESC LIMIT 1;";
 		return $wpdb->get_results($sql)[0]->jet;
 	}

}

class Dice
{
	private $sides;
	public function getSides() {
		return $this->sides;
	}

	private $possibleSides = array(1 => 4,6,8,10,12,20,100);

	public function __construct($sides=6) {

		$sides = intval($sides);

		if ( array_search($sides, $this->possibleSides) === false ) {
			return false;
		}

		$this->sides = $sides;

	}

	public function roll() {
		return mt_rand(1,$this->sides);
	}

}

class DiceSet
{

	private $content = [];
	private $result = [];
	private $total = 0;
	private $offset = 0;

	public function __construct($offset = 0) {
		$this->offset = intval($offset);
	}

	public function addDice($sides) {

		$dice = new Dice($sides);

		if ( $dice->getSides()  ) {
			$this->resetResult();
			$this->content[] = $dice;
		}

	}

	public function signature() {

		$signature = [];
		$d4 = 0;
		$d6 = 0;
		$d8 = 0;
		$d10 = 0;
		$d12 = 0;
		$d100 = 0;

		if ( ! empty($this->content) ) {
			foreach ( $this->content as $dice ) {
				switch ( $dice->getSides() ) {
					case 4: $d4++; break;
					case 6: $d6++; break;
					case 8: $d8++; break;
					case 10: $d10++; break;
					case 12: $d12++; break;
					case 100: $d100++; break;
				}
			}

			if ( $d4 ) $signature[] = ( ($d4>1) ? $d4 : "" ) . "d4";
			if ( $d6 ) $signature[] = ( ($d6>1) ? $d6 : "" ) . "d6";
			if ( $d8 ) $signature[] = ( ($d8>1) ? $d8 : "" ) . "d8";
			if ( $d10 ) $signature[] = ( ($d10>1) ? $d10 : "" ) . "d10";
			if ( $d12 ) $signature[] = ( ($d12>1) ? $d12 : "" ) . "d12";
			if ( $d100 ) $signature[] = ( ($d100>1) ? $d100 : "" ) . "d100";

			return join(" + ", $signature) . ( ($this->offset) ? ' + ' . $this->offset : '' );

		}

		return "";

	}

	public function roll() {

		$this->resetResult();

		foreach ( $this->content as $dice ) {
			$result = $dice->roll();
			$this->result[] = $result;
			$this->total += $result;
		}

		global $wpdb;
		$wpdb->insert(
			"wp_jet",
			array(
				"jet" => $this->signature() . ": " . join(" + ", $this->result) . " = " . $this->total,
				"userid" => get_current_user_id()
			)
		);

	}

	public function resetResult() {
		$this->result = [];
		$this->total = $this->offset;
	}

	public function reset() {
		$this->content = [];
		$this->resetResult();
	}

}
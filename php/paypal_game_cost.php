<?php
# Example output: https://gist.github.com/Contex/9512f87600105d86ee53/raw/6a168a55ff9c87e921de313cc84deaf7d4b7c477/gistfile1.txt

# See class for documentation
$paypal_cost = new PayPalGameCost();
$paypal_cost->setCSVFilename('paypal.csv');
$paypal_cost->setItemListPrefix('-');
$paypal_cost->setRoundDecimal(2);
$paypal_cost->displayStores(TRUE);
$paypal_cost->displayCurrency(FALSE);

$paypal_cost->displayTransactionDetails(FALSE);
$paypal_cost->displayAverageTransactionCost(TRUE);

$paypal_cost->displayDetails(TRUE);

$paypal_cost->setExchangeCurrency('USD');
$paypal_cost->setExchangeRates(array(
	'EUR' => json_decode(file_get_contents("http://rate-exchange.appspot.com/currency?from="+$paypal_cost->ExchangeCurrency+"&to=EUR"))['rate'],
	'GBP' => json_decode(file_get_contents("http://rate-exchange.appspot.com/currency?from="+$paypal_cost->ExchangeCurrency+"&to=GBP"))['rate'],
	'AUD' => json_decode(file_get_contents("http://rate-exchange.appspot.com/currency?from="+$paypal_cost->ExchangeCurrency+"&to=AUD"))['rate'],
	'NOK' => json_decode(file_get_contents("http://rate-exchange.appspot.com/currency?from="+$paypal_cost->ExchangeCurrency+"&to=NOK"))['rate'],
	'PLN' => json_decode(file_get_contents("http://rate-exchange.appspot.com/currency?from="+$paypal_cost->ExchangeCurrency+"&to=PLN"))['rate'],
	'BRL' => json_decode(file_get_contents("http://rate-exchange.appspot.com/currency?from="+$paypal_cost->ExchangeCurrency+"&to=BRL"))['rate']
));

$paypal_cost->parseData();

$paypal_cost->outputData();


class PayPalGameCost
{
	######################################################
	# DON'T EDIT BELOW UNLESS YOU KNOW WHAT YOU'RE DOING #
	######################################################

	###########
	# GENERAL #
	###########

	# CSV file name (must be in the same directory as this PHP script)
	private $csv_filename = 'paypal.csv';

	# Output list item prefix
	private $item_prefix = '-';

	# Cost round decimals
	private $round_decimal = 2;

	# Display game stores as well as bundle sites?
	private $display_stores = TRUE;

	# Display all currency and the amount, or just the total after the exchange rates?
	private $display_currency = FALSE;

	################
	# TRANSACTIONS #
	################

	# Display transaction details per store/bundle site?
	# Only displays transaction details for stores if $display_stores is set to TRUE
	private $display_transaction_details = FALSE;

	# Display average transaction cost?
	private $display_average_transaction_cost = TRUE;

	#############################
	# STORE/BUNDLE SITE DETAILS #
	#############################

	# Display details about each category (game store/bundle site)
	private $display_details = TRUE;

	#################
	# EXCHANGE RATE #
	#################

	# Which currency should the currency be exchanges to?
	private $exchange_currency = 'USD';

	# Currency exchange rates, modify these as needed. See http://www.xe.com/ for currency exchange rates
	private $exchange_rates = array(
		'EUR' => 1.36725,
		'GBP' => 1.63536,
		'AUD' => 0.89107,
		'NOK' => 0.16259,
		'PLN' => 0.32975,
		'BRL' => 0.42415
	);

	# Game stores
	private $game_stores = array(
		'WWW.Steampowered.com'       => 'Steam',
		'GamersGate AB'              => 'GamersGate',
		'Green Man Gaming Limited'   => 'Green Man Gaming',
		'GALASTORE s.r.l'            => 'Indie Gala Store',
		'CDP.pl Sp. z o.o.'          => 'CD Projekt',
		'GameFly Digital, Inc.'      => 'GameFly',
		'Get Games Ltd'              => 'Get Games',
		'Impulse, Inc.'              => 'GameStop',
		'NUUVEM JOGOS DIGITAIS S.A.' => 'Nuuvem',
		'EA Swiss Sarl'              => 'Origin'
	);

	# Game bundles
	private $game_bundles = array(
		'INDIEGALA s.r.l.'         => 'Indie Gala',
		'Focus Multimedia Limited' => 'Bundle Stars',
		'Humble Bundle, Inc.'      => 'Humble Bundle',
		'StackSocial'              => 'StackSocial',
		'Desura Pty Ltd'           => 'Indie Royale',
		'YAWMA LLC'                => 'Groupees',
		'INDIE GAME BUNDLE LLC'    => 'IndieGameStand',
		'SARL Donai'               => 'Flying Bundle',
		're:discover Inc.'         => 'Bundle Dragon',
		'IndieBundle.org'          => 'IndieBundle',
		'Kyttaro Tech Ltd'         => 'Bundle In A Box'
	);

	# Transaction types
	private $transaction_types = array(
		'Express Checkout Payment Sent',
		'Web Accept Payment Sent',
		'Payment Sent',
		'Shopping Cart Payment Sent'

	);

	# Init array
	private $data = array();
	private $data_parsed = FALSE;
	private $paypal_items = 0;

	# Returns all the data
	public function getData()
	{
		return $this->data;
	}

	# CSV file name (must be in the same directory as this PHP script)
	public function setCSVFilename($csv_filename)
	{
		$this->csv_filename = $csv_filename;
	}

	# Output list item prefix
	public function setItemListPrefix($item_prefix)
	{
		$this->item_prefix = $item_prefix;
	}

	# Cost round decimals
	public function setRoundDecimal($round_decimal)
	{
		$this->round_decimal;
	}

	# Display game stores as well as bundle sites?
	public function displayStores($display_stores)
	{
		$this->display_stores = $display_stores;
	}

	# Display all currency and the amount, or just the total after the exchange rates?
	public function displayCurrency($display_currency)
	{
		$this->display_currency = $display_currency;
	}

	# Display transaction details per store/bundle site?
	# Only displays transaction details for stores if $display_stores is set to TRUE
	public function displayTransactionDetails($display_transaction_details)
	{
		$this->display_transaction_details = $display_transaction_details;
	}

	# Display average transaction cost?
	public function displayAverageTransactionCost($display_average_transaction_cost)
	{
		$this->display_average_transaction_cost = $display_average_transaction_cost;
	}

	# Display details about each category (game store/bundle site)
	public function displayDetails($display_details)
	{
		$this->display_details = $display_details;
	}

	# Which currency should the currency be exchanges to?
	public function setExchangeCurrency($exchange_currency)
	{
		$this->exchange_currency = $exchange_currency;
	}

	# Currency exchange rates, modify these as needed. See http://www.xe.com/ for currency exchange rates
	public function setExchangeRates(array $exchange_rates)
	{
		$this->exchange_rates = $exchange_rates;
	}

	# Parse the data
	public function parseData()
	{
		# Set start time
		$this->start_time = microtime(true);

		# Load CSV data
		$csv_data = $this->csv_to_array($this->csv_filename);

		# Loop through the transaction records
		foreach ($csv_data as $csv_item) {
			$this->paypal_items++;

			# Check if transaction type is correct
			if (!in_array($csv_item['Type'], $this->transaction_types)) {
				continue;
			}

			# Convert amount to positive
			if (empty($csv_item['Amount'])) {
				$csv_item['Amount'] = $csv_item['Net'];
			}
			$csv_item['Amount'] = abs(str_replace(',', '.', $csv_item['Amount']));

			# Check stores and bundles
			if (array_key_exists($csv_item['Name'], $this->game_stores)) {
				# Check each site
				if (empty($this->data['store']['sites'][$this->game_stores[$csv_item['Name']]])) {
					$this->data['store']['sites'][$this->game_stores[$csv_item['Name']]] = array(
						'transaction_count' => 1,
						'transactions'      => array(),
						'cost'              => array(
							'total'               => $this->exchangeCurrency($csv_item['Currency'], $csv_item['Amount']),
							$csv_item['Currency'] => $csv_item['Amount'],
						)
					);
				} else {
					$this->data['store']['sites'][$this->game_stores[$csv_item['Name']]]['transaction_count']++;
					if (empty($this->data['store']['sites'][$this->game_stores[$csv_item['Name']]]['cost'][$csv_item['Currency']])) {
						$this->data['store']['sites'][$this->game_stores[$csv_item['Name']]]['cost'][$csv_item['Currency']] = $csv_item['Amount'];
					} else {
						$this->data['store']['sites'][$this->game_stores[$csv_item['Name']]]['cost'][$csv_item['Currency']] += $csv_item['Amount'];
					}
					$this->data['store']['sites'][$this->game_stores[$csv_item['Name']]]['cost']['total'] += $this->exchangeCurrency($csv_item['Currency'], $csv_item['Amount']);
				}

				# Total currency
				if (empty($this->data['store']['totals']['cost'][$csv_item['Currency']])) {
					$this->data['store']['totals']['cost'][$csv_item['Currency']] = $csv_item['Amount'];
				} else {
					$this->data['store']['totals']['cost'][$csv_item['Currency']] += $csv_item['Amount'];
				}

				# Exchange currency with rates
				if (empty($this->data['store']['totals']['cost']['total'])) {
					$this->data['store']['totals']['cost']['total'] = $this->exchangeCurrency($csv_item['Currency'], $csv_item['Amount']);
				} else {
					$this->data['store']['totals']['cost']['total'] += $this->exchangeCurrency($csv_item['Currency'], $csv_item['Amount']);
				}

				# Increase amount of transactions
				if (empty($this->data['store']['totals']['transactions'])) {
					$this->data['store']['totals']['transactions'] = 1;
				} else {
					$this->data['store']['totals']['transactions']++;
				} 

				# Transaction record
				$this->data['store']['sites'][$this->game_stores[$csv_item['Name']]]['transactions'][] = array(
					'date'      => $csv_item['Date'],
					'time'      => $csv_item['Time'],
					'time_zone' => $csv_item['Time Zone'],
					'site'      => $this->game_stores[$csv_item['Name']],
					'currency'  => $csv_item['Currency'],
					'amount'    => $csv_item['Amount']
				);

			} else if (array_key_exists($csv_item['Name'], $this->game_bundles)) {
				# Check each site
				if (empty($this->data['bundle']['sites'][$this->game_bundles[$csv_item['Name']]])) {
					$this->data['bundle']['sites'][$this->game_bundles[$csv_item['Name']]] = array(
						'transaction_count' => 1,
						'transactions'      => array(),
						'cost'              => array(
							'total'               => $this->exchangeCurrency($csv_item['Currency'], $csv_item['Amount']),
							$csv_item['Currency'] => $csv_item['Amount']
						)
					);
				} else {
					$this->data['bundle']['sites'][$this->game_bundles[$csv_item['Name']]]['transaction_count']++;
					if (empty($this->data['bundle']['sites'][$this->game_bundles[$csv_item['Name']]]['cost'][$csv_item['Currency']])) {
						$this->data['bundle']['sites'][$this->game_bundles[$csv_item['Name']]]['cost'][$csv_item['Currency']] = $csv_item['Amount'];
					} else {
						$this->data['bundle']['sites'][$this->game_bundles[$csv_item['Name']]]['cost'][$csv_item['Currency']] += $csv_item['Amount'];
					}
					$this->data['bundle']['sites'][$this->game_bundles[$csv_item['Name']]]['cost']['total'] += $this->exchangeCurrency($csv_item['Currency'], $csv_item['Amount']);
				}

				# Total currency
				if (empty($this->data['bundle']['totals']['cost'][$csv_item['Currency']])) {
					$this->data['bundle']['totals']['cost'][$csv_item['Currency']] = $csv_item['Amount'];
				} else {
					$this->data['bundle']['totals']['cost'][$csv_item['Currency']] += $csv_item['Amount'];
				}

				# Exchange currency with rates
				if (empty($this->data['bundle']['totals']['cost']['total'])) {
					$this->data['bundle']['totals']['cost']['total'] = $this->exchangeCurrency($csv_item['Currency'], $csv_item['Amount']);
				} else {
					$this->data['bundle']['totals']['cost']['total'] += $this->exchangeCurrency($csv_item['Currency'], $csv_item['Amount']);
				}

				# Increase amount of transactions
				if (empty($this->data['bundle']['totals']['transactions'])) {
					$this->data['bundle']['totals']['transactions'] = 1;
				} else {
					$this->data['bundle']['totals']['transactions']++;
				}

				# Transaction record
				$this->data['bundle']['sites'][$this->game_bundles[$csv_item['Name']]]['transactions'][] = array(
					'date'      => $csv_item['Date'],
					'time'      => $csv_item['Time'],
					'time_zone' => $csv_item['Time Zone'],
					'site'      => $this->game_bundles[$csv_item['Name']],
					'currency'  => $csv_item['Currency'],
					'amount'    => $csv_item['Amount']
				);
			}
		}
		$this->calculateTotalCost();
		$this->data_parsed = TRUE;
	}

	private function calculateTotalCost()
	{
		# Total cost
		if ($this->display_stores) {
			# Round totals
			$this->data['store']['totals']['cost']['total'] = round($this->data['store']['totals']['cost']['total'], $this->round_decimal);
			$this->data['bundle']['totals']['cost']['total'] = round($this->data['bundle']['totals']['cost']['total'], $this->round_decimal);

			# Create array with totals
			$this->total_cost = array();

			# Store totals
			foreach ($this->data['store']['totals']['cost'] as $currency => $amount) {
				if (empty($this->total_cost[$currency])) {
					$this->total_cost[$currency] = $amount;
				} else {
					$this->total_cost[$currency] += $amount;
				}
			}

			# Bundle site totals
			foreach ($this->data['bundle']['totals']['cost'] as $currency => $amount) {
				if (empty($this->total_cost[$currency])) {
					$this->total_cost[$currency] = $amount;
				} else {
					$this->total_cost[$currency] += $amount;
				}
			}
		} else {
			# Round totals
			$this->data['bundle']['totals']['cost']['total'] = round($this->data['bundle']['totals']['cost']['total'], $this->round_decimal);

			$this->total_cost = $this->data['bundle']['totals']['cost'];
		}
	}

	public function outputData()
	{
		if (!$this->data_parsed) {
			die('PayPal data has not yet been parsed, please run the parseData() function before running outputData().');
		}
		# Let's make it display as text
		header('Content-type: text/plain');

		# Output the data
		echo "* Totals *\n";
		echo "    $this->item_prefix Transactions: " . ($this->data['bundle']['totals']['transactions'] + ($this->display_stores ? $this->data['store']['totals']['transactions'] : 0)) . "\n";
		if ($this->display_stores) {
			echo "        $this->item_prefix Game stores: {$this->data['store']['totals']['transactions']}\n";
			echo "        $this->item_prefix Bundle sites: {$this->data['bundle']['totals']['transactions']}\n";
		}

		echo "    $this->item_prefix Cost:" . ((!$this->display_stores && !$this->display_currency && !$this->display_average_transaction_cost) ? "{$this->total_cost['total']} $this->exchange_currency" : '') . "\n";
		if ($this->display_stores) {
			if ($this->display_average_transaction_cost) {
				echo "        $this->item_prefix Average cost for " . ($this->data['store']['totals']['transactions'] + $this->data['bundle']['totals']['transactions']) 
				   . " transactions: " . round(($this->total_cost['total'] / ($this->data['store']['totals']['transactions'] + $this->data['bundle']['totals']['transactions'])), $this->round_decimal) . " $this->exchange_currency\n";
			}
			echo "        $this->item_prefix Total: {$this->total_cost['total']} $this->exchange_currency\n";
			if ($this->display_currency) {
				foreach ($this->total_cost as $currency => $amount) {
					if ($currency == 'total') {
						continue;
					}
					echo "            $this->item_prefix $currency: $amount\n";
				}
			}
			echo "        $this->item_prefix Game stores: {$this->data['store']['totals']['cost']['total']} $this->exchange_currency\n";
			if ($this->display_currency) {
				foreach ($this->data['store']['totals']['cost'] as $currency => $amount) {
					if ($currency == 'total') {
						continue;
					}
					echo "            $this->item_prefix $currency: $amount\n";
				}
			}
			echo "        $this->item_prefix Bundle sites: {$this->data['bundle']['totals']['cost']['total']} $this->exchange_currency\n";
			if ($this->display_currency) {
				foreach ($this->data['bundle']['totals']['cost'] as $currency => $amount) {
					if ($currency == 'total') {
						continue;
					}
					echo "            $this->item_prefix $currency: $amount\n";
				}
			}
		} else if ($this->display_currency) {
			if ($this->display_average_transaction_cost) {
				echo "        $this->item_prefix Average cost for " . $this->data['bundle']['totals']['transactions'] 
				   . " transactions: " . round(($this->total_cost['total'] / $this->data['bundle']['totals']['transactions']), $this->round_decimal) . " $this->exchange_currency\n";
			}
			echo "        $this->item_prefix Total: {$this->total_cost['total']} $this->exchange_currency\n";
			foreach ($this->total_cost as $currency => $amount) {
				if ($currency == 'total') {
						continue;
					}
				echo "        $this->item_prefix $currency: $amount\n";
			}
		} else if ($this->display_average_transaction_cost) {
			echo "        $this->item_prefix Average cost for " . $this->data['bundle']['totals']['transactions'] 
			   . " transactions: " . round(($this->total_cost['total'] / $this->data['bundle']['totals']['transactions']), $this->round_decimal) . " $this->exchange_currency\n";
			echo "        $this->item_prefix Total: {$this->total_cost['total']} $this->exchange_currency\n";
		}

		# Display details
		if ($this->display_details) {
			if ($this->display_stores) {
				$this->outputDetails('store', 'Game stores');
			}
			$this->outputDetails('bundle', 'Bundle sites');
		}
		$end_time = microtime(true);
		echo "\nParsed through $this->paypal_items PayPal history activity item(s) in " . ($end_time - $this->start_time) . " second(s).";
	}

	private function outputDetails($type = 'bundle', $string = 'Bundle sites')
	{
		# Sort array alphabetical
		ksort($this->data[$type]['sites']);

		# Output data
		echo "\n* $string *\n";
		foreach ($this->data[$type]['sites'] as $site_name => $site_data) {
			echo "    $this->item_prefix $site_name:\n";
			echo "        $this->item_prefix Transactions: {$site_data['transaction_count']}\n";
			if ($this->display_average_transaction_cost) {
				echo "        $this->item_prefix Average cost for " . $site_data['transaction_count'] 
				   . " transactions: " . round(($site_data['cost']['total'] / $site_data['transaction_count']), $this->round_decimal) . " $this->exchange_currency\n";
			}
			echo "        $this->item_prefix Cost: " . round($site_data['cost']['total'], $this->round_decimal) . " $this->exchange_currency\n";
			if ($this->display_currency) {
				foreach ($site_data['cost'] as $currency => $amount) {
					if ($currency == 'total') {
						continue;
					}
					echo "            $this->item_prefix $currency: $amount\n";
				}
			}
			if ($this->display_transaction_details) {
				# Reverse transactions, last -> new
				$site_data['transactions'] = array_reverse($site_data['transactions']);

				# Output data
				echo "        $this->item_prefix Transaction details:\n";
				foreach ($site_data['transactions'] as $transaction_id => $transaction) {
					echo "            $this->item_prefix #" . ($transaction_id + 1) . " {$transaction['date']} {$transaction['time']} "
					   . "{$transaction['time_zone']}: {$transaction['amount']} {$transaction['currency']} = " 
					   . round($this->exchangeCurrency($transaction['currency'], $transaction['amount']), $this->round_decimal) . " $this->exchange_currency\n";
				}
			}
		}
	}

	public function exchangeCurrency($currency, $amount) 
	{
		# Check if currency is the same as the original currency.
		if ($currency == $this->exchange_currency) {
			return $amount;
		}

		# Check if exchange rate for currency exists
		if (!array_key_exists($currency, $this->exchange_rates)) {
			die("ERROR: Missing exchange rate for currency: $currency");
		}
		return $this->exchange_rates[$currency] * $amount;
	}

	/**
	 * Convert a comma separated file into an associated array.
	 * The first row should contain the array keys.
	 * 
	 * Example:
	 * 
	 * @param string $filename Path to the CSV file
	 * @param string $delimiter The separator used in the file
	 * @return array
	 * @link http://gist.github.com/385876
	 * @author Jay Williams <http://myd3.com/>
	 * @copyright Copyright (c) 2010, Jay Williams
	 * @license http://www.opensource.org/licenses/mit-license.php MIT License
	 */
	public function csv_to_array($filename='', $delimiter=',')
	{
		if(!file_exists($filename) || !is_readable($filename))
			return FALSE;
		
		$header = NULL;
		$data = array();
		if (($handle = fopen($filename, 'r')) !== FALSE)
		{
			while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
			{
				if (!$header) 
				{
					$header = $row;
					# Modified Contex: remove space caused by PayPal export in the header
					array_walk($header, create_function('&$val', '$val = trim($val);'));
				}
				else {
					$data[] = array_combine($header, $row);
				}
			}
			fclose($handle);
		}
		return $data;
	}
}
?>

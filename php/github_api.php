<?php
/*
 * This file is part of Code-Snipets <https://github.com/Contex/Code-Snipets/>.
 *
 * GithubAPI is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GithubAPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$githubAPI = new GithubAPI('Contex', 'XenAPI');
$githubAPI->setLocalDir('/home/contex/localdir/');
$githubAPI->setToken('SET_TOKEN_HERE');
$githubAPI->setDebug(TRUE);
header('content-type: text/plain');

try {
	header('content-type: text/plain');
	print_r($githubAPI->getLastLocalCommit(TRUE));
} catch (Exception $e) {
	echo 'Error with grabbing commit: ' . $e->getMessage();
}

/**
* GitSync allows to sync a git repo to your server.
*/
class GithubAPI {
	const API_URL     = 'https://api.github.com/';
	const LAST_COMMIT = '.last_commit';
	const VERSION     = '1.0';
	private $logging_levels = array('INFO', 'WARNING', 'ERROR');
	/**
	* Default constructor.
	*/
	public function __construct($owner, $repo, $branch = 'master') {
		date_default_timezone_set('UTC');
		// Set all the variables.
		$this->owner  = $owner;
		$this->repo   = $repo;
		$this->branch = $branch;
	}

	public function compareCommit($sha, $branch = NULL) {
		if ($branch == NULL) {
			$branch = $this->getBranch();
		}
		return json_decode(HTTPRequest::getResults(
			'get', 
			self::API_URL, 
			'repos/' 
				. $this->getOwner() 
				. '/'
				. $this->getRepo()
				. '/compare/' 
				. $sha
				. '...'
				. $branch
		), TRUE);
	}

	public function compareLastCommit() {
		return $this->compareCommit($this->getLastLocalCommit());
	}

	public function forcePull() {
		if ($this->getLocalDir() == NULL) {
			throw new Exception('Local directory has not been set.');
		}
		$file = $this->getZipball();

		$zip = new ZipArchive;
		$res = $zip->open($file);
		if ($res !== TRUE) {
			unlink($file);
			throw new Exception('Could not extract zip: ' . $file);
		}

		$temp_directory = sys_get_temp_dir()
						. '/' . $this->getOwner() 
				        . '-' . $this->getRepo() 
				        . '-' . $this->getBranch() 
				        . '-' . time();

		$zip->extractTo($temp_directory);
		$zip->close();
		unlink($file);

		$directories = scandir($temp_directory);

		foreach ($directories as $directory) {
			if ($directory === '.' or $directory === '..') {
				continue;
			}

			if (is_dir($temp_directory . '/' . $directory)) {
				$this->recursiveMove(
					$temp_directory . '/' . $directory, 
					$this->getLocalDir()
				);
			}
		}
		rmdir($temp_directory);
		$last_commit = $this->getLastCommit();
		$this->setLastLocalCommit($last_commit['sha']);
	}

	public function isDebug() {
		return isset($this->debug_mode) && $this->debug_mode;
	}

	public function setDebug($debug_mode) {
		$this->debug_mode = $debug_mode;
	}

	/**
	* Returns the data of a specific commit.
	*/
	public function getCommit($sha) {
		return json_decode(HTTPRequest::getResults(
			'get', 
			self::API_URL, 
			'repos/' 
				. $this->getOwner() 
				. '/'
				. $this->getRepo()
				. '/git/commits/' 
				. $sha
		), TRUE);
	}

	/**
	* Get the branch.
	*/
	public function getBranch() {
		return $this->branch;
	}

	/**
	* Set the branch.
	*/
	public function setBranch($branch) {
		$this->branch = $branch;
	}

	/**
	* Get the dir.
	*/
	public function getLocalDir() {
		return $this->dir;
	}

	/**
	* Set the dir.
	*/
	public function setLocalDir($dir) {
		$this->dir = $dir;
	}

	/**
	* Returns the last commit.
	*/
	public function getLastCommit() {
		$commits = $this->getLatestCommits();
		return $commits[0];
	}

	/**
	* Returns the latest commits.
	*/
	public function getLatestCommits() {
		return json_decode(HTTPRequest::getResults(
			'get', 
			self::API_URL, 
			'repos/' 
				. $this->getOwner() 
				. '/'
				. $this->getRepo()
				. '/commits'
		), TRUE);
	}

	/**
	* Returns the last local commit.
	*/
	public function getLastLocalCommit() {
		if ($this->getLocalDir() == NULL) {
			throw new Exception('Local directory has not been set.');
		}

		if (!file_exists($this->getLocalDir() . '/' . self::LAST_COMMIT)) {
			throw new Exception('Could not find the last local commit, you can force pull the repo by running forcePull().');
		} else {
			return file_get_contents($this->getLocalDir() . '/' . self::LAST_COMMIT);
		}
	}

	public function setLastLocalCommit($commit_sha) {
		file_put_contents($this->getLocalDir() . '/' . self::LAST_COMMIT, $commit_sha);
	}

	/**
	* Get the owner that owns the repo.
	*/
	public function getOwner() {
		return $this->owner;
	}

	/**
	* Set the owner.
	*/
	public function setOwner($owner) {
		$this->owner = $owner;
	}

	/**
	* Get the repo.
	*/
	public function getRepo() {
		return $this->repo;
	}

	/**
	* Set the repo.
	*/
	public function setRepo($repo) {
		$this->repo = $repo;
	}

	/**
	* Get the token.
	*/
	public function getToken() {
		return $this->token;
	}

	/**
	* Set the token.
	*/
	public function setToken($token) {
		$this->token = $token;
	}

	public function getSyncURL() {
		return  'repos/' . $this->getUser() . '/' . $this->getRepo() . '/compare/' . $lastcommit . '...' . $this->getBranch();
	}

	public function getZipball() {
		if ($this->getLocalDir() == NULL) {
			throw new Exception('Local directory has not been set.');
		}
		$file_name = sys_get_temp_dir()
				   . '/' . $this->getOwner() 
				   . '-' . $this->getRepo() 
				   . '-' . $this->getBranch() 
				   . '-' . time() . '.zip';
		$file = HTTPRequest::getResults(
			'file', 
			self::API_URL . 'repos/'
				. $this->getOwner() 
				. '/'
				. $this->getRepo()
				. '/zipball/'
				. $this->getBranch(), 
			$file_name
		);
		return $file_name;
	}

	private function logToFile($string, $logging_level = 'INFO', $prefix = NULL) {
		$logging_level = strtoupper($logging_level);
		if (!in_array($logging_level, $this->logging_levels)) {
			$logging_level = 'INFO';
		}
		$string = date("Y-m-d H:i:s") 
				. ' - [' . $logging_level . '] - ' 
				. ($prefix != NULL ? '[' . strtoupper($prefix) . '] - ' : '')
				. $string;
		if (isset($this->debug_mode) && $this->debug_mode) {
			echo $string . "\n";
			#file_put_contents('debug.txt', $string . "\n", FILE_APPEND);
		} else if ($logging_level != 'DEBUG') {
			echo $string . "\n";
			#file_put_contents('log.txt', $string . "\n", FILE_APPEND);
		}
	}

	public function patchCommit($sha, $branch = NULL) {
		$this->logToFile('Attempting to patch from commit: ' . $sha, 'DEBUG');
		if ($branch == NULL) {
			$branch = $this->getBranch();
		}
		$data = $this->compareCommit($sha, $branch);
		$this->logToFile('Found ' . count($data['commits']) . ' commit(s) since commit: ' . $sha, 'DEBUG');
		if (count($data['commits']) > 0) {
			foreach ($data['files'] as $file) {
				$patch_file = sys_get_temp_dir() 
						   . '/' . $this->getOwner()
						   . '-' . $this->getRepo()
						   . '-' . $this->getBranch()
						   . '-patch-' . $file['sha'];
			    $this->logToFile(
			    	'Creating patch file for file "' . $file['filename'] . '": ' . $patch_file, 
			    	'DEBUG'
		    	);
		    	$this->logToFile(
			    	'Patch info of "' . $file['filename'] . '": ' 
			    		. 'sha=' . $file['sha'] . ' | '
			    		. 'status=' . $file['status'] . ' | '
			    		. 'additions=' . $file['additions'] . ' | '
			    		. 'deletions=' . $file['deletions'] . ' | '
			    		. 'changes=' . $file['changes'],
			    	'DEBUG'
		    	);
				file_put_contents(
					$patch_file, 
					$file['patch']
				);
				$exec_command = '/usr/bin/patch \'' . $this->getLocalDir()
							  . '/' . $file['filename'] . '\' '
							  . '\'' . $patch_file . '\'';
				$this->logToFile(
					'Executing exec command: "' . $exec_command . '"', 
					'DEBUG'
				);
				exec($exec_command, $output);
				$this->logToFile(
					'Patch results for ' . $patch_file . ' are displayed below:', 
					'DEBUG'
				);
				$failed = FALSE;
				foreach ($output as $patch_result) {
					if ($patch_result == 'Skipping patch.' || strpos($patch_result, 'FAILED') !== FALSE) {
						$failed = TRUE;
					}
					$this->logToFile(
						"\t\t" . $patch_result, 
						'DEBUG'
					);
				}
				if ($failed) {
					$this->logToFile(
						'Could not apply patch from ' . $patch_file 
							. ' to ' . $this->getLocalDir() . '/' . $file['filename'] . '!', 
						'ERROR'
					);
					throw Exception('Could not apply patch from ' . $patch_file 
							. ' to ' . $this->getLocalDir() . '/' . $file['filename'] . '!');
				}
				if (file_exists($this->getLocalDir() . '/' . $file['filename'] . '.orig')) { 
					unlink($this->getLocalDir() . '/' . $file['filename'] . '.orig'); 
				}
			}
			$current_commit = current($data['commits']);
			$this->logToFile(
				'Successfully patched ' . $this->getOwner() . '/' . $this->getRepo() 
					. '/' . $branch . ' to commit: ' . $current_commit['sha'], 
				'INFO'
			);
			$this->setLastLocalCommit($current_commit['sha']);
		} else {
			$this->logToFile(
				'Did not find anything to patch after commit: ' . $sha, 
				'DEBUG'
			);
		}
	}

	public function patchLastCommit() {
		return $this->patchCommit($this->getLastLocalCommit());
	}

	private function recursiveMove($source, $destination) {
		$this->logToFile('Attempting to move files and directories from: ' . $source . ' to ' . $destination, 'DEBUG');
		$handle = opendir($source); 
		@mkdir($destination); 
		if (!$handle) {
			throw Exception('Could not open directory: ' . $source);
		}
		$bytes_moved = 0;
		while (FALSE !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..' ) { 
				if (is_dir($source . '/' . $file)) {
					$this->logToFile('Found inner directory while scanning directory ' . $source . ': ' . $file, 'DEBUG');
					$this->recursiveMove($source . '/' . $file, $destination . '/' . $file); 
				} else { 
					$source_file_size = filesize($source . '/' . $file);
					$source_file_sum = md5_file($source . '/' . $file);
					$this->logToFile(
						'Found file while scanning directory ' . $source . ': ' . $file . ' (' . $source_file_size . ' bytes)', 
						'DEBUG'
					);
					$this->logToFile(
						'Attempting to move file ' . $source . '/' . $file 
							. ' (' . $source_file_size . ' bytes) to ' . $destination . '/' . $file, 
						'DEBUG'
					);
					$this->logToFile(
						'Source file ' . $source . '/' . $file 
							. ' md5 checksum: ' . $source_file_sum, 
						'DEBUG',
						'MD5_CHECKSUM'
					);
					if (file_exists($destination . '/' . $file)) {
						$destination_file_size = filesize($destination . '/' . $file);
						$destination_file_sum = md5_file($destination . '/' . $file);
						$this->logToFile(
							'Desination file ' . $destination . '/' . $file 
								. ' md5 checksum: ' . $destination_file_sum, 
							'DEBUG',
							'MD5_CHECKSUM'
						);
						$this->logToFile(
							'File from  ' . $source . '/' . $file . ' (' . $source_file_size . ' bytes) already exists in ' 
								. $destination . '/' . $file . ' (' . $destination_file_size . ' bytes)',
							'WARNING',
							'FILE_EXISTS'
						);
						if ($source_file_sum == $destination_file_sum) {
							$this->logToFile(
								'File from ' . $source . '/' . $file . ' has the same md5 checksum as file in ' 
									. $destination . '/' . $file . ', skipping file..',
								'DEBUG',
								'SAME_MD5_CHECKSUM'
							);
							continue;
						}
					}
					$move = rename(
						$source . '/' . $file, 
						$destination . '/' . $file
					);
					if (!$move) {
						$this->logToFile(
							'Failed moving  ' . $source . '/' . $file 
								. ' (' . $source_file_size . ' bytes) to ' . $destination . '/' . $file, 
							'ERROR',
							'FAILED_MOVING'
						);
						unset($source_file_size);
						if (isset($destination_file_size)) {
							unset($destination_file_size);
						}
						closedir($handle); 
						$this->removeDirectory($source);
						throw new Exception(
							'Failed moving file: "' . $source . '/' . $file
							. '" to "' . $destination . '/' . $file . '"'
						);
					} else {
						if (isset($destination_file_size)) {
							$this->logToFile(
								'Successfully replaced ' . $destination . '/' . $file . ' (' . $destination_file_size . ' bytes) with ' 
									. $source . '/' . $file . ' (' . $source_file_size . ' bytes)', 
								'DEBUG',
								'FILE_REPLACED'
							);
							unset($destination_file_size);
						} else {
							$this->logToFile(
								'Successfully moved  ' . $source . '/' . $file . ' (' . $source_file_size . ' bytes) to ' 
									. $destination . '/' . $file . '(' . $destination_file_size . ' bytes)', 
								'DEBUG',
								'FILE_MOVED'
							);
						}
					}
					$bytes_moved += $source_file_size;
					unset($source_file_size);
				} 
			} 
		} 
		if ($bytes_moved > 0) {
			$this->logToFile(
				'Successfully moved a total of ' . $bytes_moved . ' bytes from ' 
					. $source . ' to ' . $destination, 
				'DEBUG',
				'BYTES_MOVED'
			);
		} else {
			$this->logToFile(
				'Nothing was moved from ' 
					. $source . ' to ' . $destination, 
				'DEBUG',
				'NOTHING_MOVED'
			);
		}
		closedir($handle); 
		$this->removeDirectory($source);
	}

	private function removeDirectory($directory) {
		$iterator = new RecursiveDirectoryIterator($directory);
		$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
		foreach($files as $file) {
			if ($file->getFilename() === '.' || $file->getFilename() === '..') {
				continue;
			}
			if ($file->isDir()) {
				if (!rmdir($file->getRealPath())) {
					$this->logToFile(
						'Unable to remove directory ' . $file->getRealPath() . ', please remove it manually', 
						'ERROR',
						'REMOVE_DIRECTORY'
					);
					throw new Exception(
						'Unable to remove directory ' . $file->getRealPath() . ', please remove it manually'
					);
				} else {
					$this->logToFile(
						'Successfully removed directory: '. $file->getRealPath(),
						'DEBUG',
						'REMOVED_DIRECTORY'
					);
				}
			} else {
				if (!unlink($file->getPathname())) {
					$this->logToFile(
						'Unable to remove file ' . $file->getPathname() . ', please remove it manually', 
						'ERROR',
						'REMOVE_FILE'
					);
					throw new Exception(
						'Unable to remove file ' . $file->getPathname() . ', please remove it manually'
					);
				} else {
					$this->logToFile(
						'Successfully removed file: '. $file->getPathname(),
						'DEBUG',
						'REMOVED_FILE'
					);
				}
			}
		}
		if (!rmdir($directory)) {
			$this->logToFile(
				'Unable to remove directory ' . $directory . ', please remove it manually', 
				'ERROR',
				'REMOVE_DIRECTORY'
			);
			throw new Exception(
				'Unable to remove directory ' . $directory . ', please remove it manually'
			);
		} else {
			$this->logToFile(
				'Successfully removed directory: '. $directory,
				'DEBUG',
				'REMOVED_DIRECTORY'
			);
		}
	}
}

class HTTPRequest {
	public static function getResults($action, $url, $data, $extra_data = NULL) {
		switch(strtolower($action)) {
			case 'file':
				return self::getFILEResults($url, $data, $extra_data);
			case 'post':
				return self::getPOSTResults($url, $data, $extra_data);
			case 'get':
			default:
				return self::getGETResults($url, $data, $extra_data);
		}
	}

	public static function getGETResults($get_url, $get_fields, $extra_data = NULL) {
		if (is_array($get_fields)) {
			$get_fields = http_build_query($get_fields);
		}

		// Check if cURL is available.
		if (!is_callable('curl_init')) {
			throw new Exception('Could not find cURL');
		}
		// cURL was found avaiable.

		// Initialize cURL with the input values.
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $get_url . $get_fields);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_ENCODING, 1);
		curl_setopt($curl_handle, CURLOPT_USERAGENT, 'GitHub API ' . GithubAPI::VERSION . ' by Contex');
		if ($extra_data != NULL && is_array($extra_data)) {
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $extra_data);
		}
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, '3');

		// Grab thet data of the cURL data.
		$results = curl_exec($curl_handle);

		// Check if something went wrong with the request.
		if ($results === FALSE) {
			// The cURL request failed, throw exception.
			throw new Exception(
				'Request failed with cURL error: ' . curl_error($curl_handle)
			);
		}

		// Get the HTTP status code.
		$http_status_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

		// Close th cURL handle.
		curl_close($curl_handle);

		// Check if the status code was 200 (OK).
		if ($http_status_code != 200) {
			// The request failed, response header did not return status 200, throw exception.
			throw new Exception(
				'Request failed. HTTP status code was not 200 for url "'
			   . $get_url . $get_fields . '": ' . $http_status_code
			   . ' = ' . $results
			);
		}
		return $results;
	}

	public static function getFILEResults($file_url, $output_file, $extra_data = NULL) {
		// Check if cURL is available.
		if (!is_callable('curl_init')) {
			throw new Exception('Could not find cURL');
		}
		// cURL was found avaiable.

		$file_handle = fopen($output_file, 'w');

		// Initialize cURL with the input values.
		set_time_limit(0); 

		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $file_url);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_USERAGENT, 'GitHub API v' . GithubAPI::VERSION . ' by Contex');
		if ($extra_data != NULL && is_array($extra_data)) {
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $extra_data);
		}
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, '30');
		curl_setopt($curl_handle, CURLOPT_FILE, $file_handle);

		// Grab thet data of the cURL data.
		$results = curl_exec($curl_handle);

		fclose($file_handle);

		// Check if something went wrong with the request.
		if ($results === FALSE) {
			// The cURL request failed, throw exception.
			throw new Exception(
				'Request failed with cURL error: ' . curl_error($curl_handle)
			);
		}

		// Get the HTTP status code.
		$http_status_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

		// Close th cURL handle.
		curl_close($curl_handle);

		// Check if the status code was 200 (OK).
		if ($http_status_code != 200) {
			// The request failed, response header did not return status 200, throw exception.
			throw new Exception(
				'Request failed. HTTP status code was not 200 for url "'
			   . $file_url . '": ' . $http_status_code . ' = ' . $results
			);
		}
		return $results;
	}

	public static function getPOSTResults($post_url, $post_fields, $extra_data = NULL) {
		if (!is_array($post_fields)) {
			throw new Exception(
				'Request failed with illegal argument error: post_fields has to be an array'
			);
		}

		// Check if cURL is available.
		if (!is_callable('curl_init')) {
			throw new Exception('Could not find cURL');
		}
		// cURL was found avaiable.

		// Initialize cURL with the input values.
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $post_url);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_POST, 1);
		curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($curl_handle, CURLOPT_USERAGENT, 'GitHub API v' . GithubAPI::VERSION . ' by Contex');
		if ($extra_data != NULL && is_array($extra_data)) {
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $extra_data);
		}
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT, '3');

		// Grab thet data of the cURL data.
		$results = curl_exec($curl_handle);

		// Check if something went wrong with the request.
		if ($results === FALSE) {
			// The cURL request failed, throw exception.
			throw new Exception(
				'Request failed with cURL error: ' . curl_error($curl_handle)
			);
		}

		// Get the HTTP status code.
		$http_status_code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

		// Close th cURL handle.
		curl_close($curl_handle);

		// Check if the status code was 200 (OK).
		if ($http_status_code != 200) {
			// The request failed, response header did not return status 200, throw exception.
			throw new Exception(
				'Request failed. HTTP status code was not 200 for url "'
			   . $post_url . http_build_query($post_fields) . '": ' . $http_status_code
			   . ' = ' . $results
			);
		}
		return $results;
	}
}
?>

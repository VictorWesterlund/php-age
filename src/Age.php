<?php

	namespace Age;

	// Allowed shell commands
	enum ShellCommands: string {
		case KEYGEN = "age-keygen";
		case AGE = "age";
	}

	class FileEncryption {
		private string $input;
		// Enable or disable --armor flag
		private bool $armor = false;

		public function __construct(mixed $input) {
			if (is_string($input)) {
				// Throw if file is not readable
				if (!is_readable($input)) {
					throw new \Exception("Failed to open input file '{$input}'");
				}
			}

			// Resolve input path from resource
			$this->input = is_resource($input) ? stream_get_meta_data($input)["uri"] : $input;
		}

		/* ---- */

		// Execute a shell command from enum of allowed commands
		private static function exec(ShellCommands $cmd, string $args = ""): string|null {
			return shell_exec("{$cmd->value} {$args} 2>&1");
		}

		// Parent directory of $output is writeable or throw
		private static function is_writable_or_throw(string $path): true {
			if (!is_writable(dirname($path))) {
				throw new \Exception("Output location '{$path}' is not writeable for current user");
			}
			return true;
		}

		// Generate asymmetric key pair
		private static function keygen(): array {
			// Generate age key pair
			$keygen = explode(PHP_EOL, self::exec(ShellCommands::KEYGEN));
			// Remove first line of output
			array_shift($keygen);

			// Return asymmetric key pair strings as assoc array
			return [
				// Extract public key from second line of age-keygen output
				"public"  => substr($keygen[1], 14),
				// Return generated key pair
				"private" => implode(PHP_EOL, $keygen)
			];
		}

		/* ---- */

		// Enable PEM encoding when encrypting
		public function armor(): self {
			$this->armor = true;
			return $this;
		}

		// Decrypt a file using a provided private key string and output file name
		public function decrypt(string $key_file, string $output): true {
			$this->is_writable_or_throw($output);

			// Throw if private key file is not readable
			if (!is_readable($key_file)) {
				throw new \Exception("Failed to open private key file '{$key_file}'");
			}
			
			// Attempt to decrypt file using private key file
			$decrypt = $this->exec(ShellCommands::AGE, "--decrypt -i {$key_file} -o {$output} {$this->input}");

			// Decryption failed
			if (!is_null($decrypt)) {
				throw new \Exception("Failed to decrypt file '{$this->input}'");
			}

			return true;
		}

		// Encrypt a file and return its private key string
		public function encrypt(string $output, string|null $output_key = null): string|false {
			$this->is_writable_or_throw($output);

			// Generate asymmetric keypair
			$key = $this->keygen();
			// Add --armor string if PEM encoding is enabled
			$armor = $this->armor ? "--armor" : "";

			// Encrypt file to output using age
			$encrypt = $this->exec(ShellCommands::AGE, "--encrypt -r {$key["public"]} {$armor} -o {$output} {$this->input}");

			// Write private key to file
			if (is_null($encrypt) && $output_key) {
				$this->is_writable_or_throw($output_key);

				// Write private key to file
				file_put_contents($output_key, $key["private"]);

				// Throw if private key file could not be created
				if (!is_file($output_key)) {
					throw new \Exception("Failed to write private key file to '{$output_key}'");
				}
			}

			// Return private key if $encrypt returns null
			return is_null($encrypt) ? $key["private"] : false;
		}
	}
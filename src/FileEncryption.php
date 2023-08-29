<?php

	namespace Age;

	// Allowed shell commands
	enum ShellCommands: string {
		case KEYGEN = "age-keygen";
		case AGE = "age";
	}

	class FileEncryption {
		private string $input;
		private ?string $public_key = null;
		private ?string $private_key = null;
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
		private static function exec(ShellCommands $cmd, string $args = ""): ?string {
			return shell_exec("{$cmd->value} {$args} 2>&1");
		}

		// File is readable or throw
		private static function is_readable_or_throw(string $path): true {
			if (!is_file($path) || !is_readable($path)) {
				throw new \Exception("Input file '{$path}' is not readable for current user");
			}
			return true;
		}

		// Parent directory of $output is writeable or throw
		private static function is_writable_or_throw(string $path): true {
			if (!is_writable(dirname($path))) {
				throw new \Exception("Output location '{$path}' is not writeable for current user");
			}
			return true;
		}

		// Parse and extract public key from age-keygen output
		private static function parse_public_key(string|array $input): string {
			// Split age-keygen output by line
			$lines = !is_array($input) ? explode(PHP_EOL, $input, 3) : $input;

			// Public key will be on second line if we received an age-keygen string
			// Otherwise we will assume we got the public key string directly
			$public_key = $lines[count($lines) > 0 ? 1 : 0];
			// Strip prefix if present
			return str_replace("# public key: ", "", $public_key);
		}

		private function parse_private_key(string|array $input): string {
			// Split age-keygen output by line
			$lines = !is_array($input) ? explode(PHP_EOL, $input, 3) : $input;
			// Private key will be on the 3rd line of an age-keygen output
			return $lines[2];
		}

		/* ---- */

		// Enable PEM encoding when encrypting
		public function armor(): self {
			$this->armor = true;
			return $this;
		}

		public function public_key(string $input): self {
			// Get public key from age-keygen file
			if (is_file($input)) {
				$this->is_readable_or_throw($input);

				$input = self::parse_public_key(file_get_contents($input));
			}

			$this->public_key = $input;
			return $this;
		}

		public function private_key(string $input): self {
			$this->is_readable_or_throw($input);

			$this->private_key = $input;
			return $this;
		}

		// Generate asymmetric key pair and optionally write to file
		public function keygen(?string $output = null): self {
			// Generate age key pair
			$keygen = explode(PHP_EOL, self::exec(ShellCommands::KEYGEN));
			// Remove first line of output
			array_shift($keygen);

			// Set global key properties
			$this->public_key(self::parse_public_key($keygen));
			$this->private_key = implode(PHP_EOL, $keygen);

			// Write generated key pair to file
			if ($output) {
				$this->is_writable_or_throw($output);

				file_put_contents($output, implode(PHP_EOL, $keygen));
			}

			return $this;
		}

		/* ---- */

		// Return key pair as assoc array
		public function get_keypair(): array {
			return [
				"public"  => $this->public_key,
				"private" => $this->private_key
			];
		}

		// Decrypt a file using a provided private key string and output file name
		public function decrypt(string $output): true {
			$this->is_writable_or_throw($output);
			
			// Decrypt file using private key file
			$cmd = "--decrypt -i {$this->private_key} -o {$output} {$this->input}";
			$decrypt = $this->exec(ShellCommands::AGE, $cmd);

			// Decryption failed
			if (!is_null($decrypt)) {
				throw new \Exception("Failed to decrypt file '{$this->input}'");
			}

			return true;
		}

		// Encrypt a file and return its private key string
		public function encrypt(string $output): array {
			$this->is_writable_or_throw($output);

			// Add --armor flag if PEM encoding is enabled
			$armor = $this->armor ? "--armor" : "";
			// Encrypt file to output using age
			$cmd = "--encrypt -r {$this->public_key} {$armor} -o {$output} {$this->input}";
			$encrypt = $this->exec(ShellCommands::AGE, $cmd);

			if (!is_null($encrypt)) {
				throw new \Exception("Failed to encrypt '{$this->input}' to '{$output}' using public key '{$this->public_key}'");
			}

			// Return keypair
			return $this->get_keypair();
		}
	}

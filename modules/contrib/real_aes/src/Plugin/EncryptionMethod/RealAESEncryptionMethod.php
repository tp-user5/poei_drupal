<?php

namespace Drupal\real_aes\Plugin\EncryptionMethod;

use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use \Defuse\Crypto\Crypto;
use \Defuse\Crypto\Exception as Ex;
use \Defuse\Crypto\Key;
use \Defuse\Crypto\Encoding;

/**
 * Class RealAESEncryptionMethod.
 *
 * @EncryptionMethod(
 *   id = "real_aes",
 *   title = @Translation("Authenticated AES (Real AES)"),
 *   description = "Authenticated encryption based on AES-128 in CBC mode. Verifies ciphertext integrity via an Encrypt-then-MAC scheme using HMAC-SHA256.",
 *   key_type_group = {"encryption"}
 * )
 */
class RealAESEncryptionMethod extends EncryptionMethodBase implements EncryptionMethodInterface {

  /**
   * {@inheritdoc}
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];

    if (!class_exists('\Defuse\Crypto\Crypto')) {
      $errors[] = $this->t('Defuse PHP Encryption library is not correctly installed.');
    }

    // Check if the key size meets the requirement.
    if (strlen($key) != Key::KEY_BYTE_SIZE) {
      $errors[] = $this->t("This encryption method requires a @size byte key.", ['@size' => Key::KEY_BYTE_SIZE]);
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key, $options = []) {
    try {
      // Defuse PHP-Encryption requires a key object instead of a string.
      $key = Encoding::saveBytesToChecksummedAsciiSafeString(Key::KEY_CURRENT_VERSION, $key);
      $key = Key::loadFromAsciiSafeString($key);
      return Crypto::encrypt((string) $text, $key);
    }
    catch (Ex\CryptoException $ex) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key, $options = []) {
    try {
      // Defuse PHP-Encryption requires a key object instead of a string.
      $key = Encoding::saveBytesToChecksummedAsciiSafeString(Key::KEY_CURRENT_VERSION, $key);
      $key = Key::loadFromAsciiSafeString($key);
      return Crypto::decrypt($text, $key);
    }
    catch (Ex\CryptoException $ex) {
      return FALSE;
    }
  }

}

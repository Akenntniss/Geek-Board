<?php

class Cache {
    private static $instance = null;
    private $redis;
    private $prefix = 'app_cache:';
    private $defaultTTL = 3600; // 1 heure par défaut

    private function __construct() {
        try {
            $this->redis = new Predis\Client([
                'scheme' => 'tcp',
                'host'   => '127.0.0.1',
                'port'   => 6379,
            ]);
        } catch (Exception $e) {
            error_log("Erreur de connexion Redis: " . $e->getMessage());
            $this->redis = null;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function set($key, $value, $ttl = null) {
        if (!$this->redis) return false;
        
        $ttl = $ttl ?? $this->defaultTTL;
        $serializedValue = serialize($value);
        
        try {
            return $this->redis->setex($this->prefix . $key, $ttl, $serializedValue);
        } catch (Exception $e) {
            error_log("Erreur lors de la mise en cache: " . $e->getMessage());
            return false;
        }
    }

    public function get($key) {
        if (!$this->redis) return null;
        
        try {
            $value = $this->redis->get($this->prefix . $key);
            return $value ? unserialize($value) : null;
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du cache: " . $e->getMessage());
            return null;
        }
    }

    public function delete($key) {
        if (!$this->redis) return false;
        
        try {
            return $this->redis->del($this->prefix . $key);
        } catch (Exception $e) {
            error_log("Erreur lors de la suppression du cache: " . $e->getMessage());
            return false;
        }
    }

    public function clear() {
        if (!$this->redis) return false;
        
        try {
            $keys = $this->redis->keys($this->prefix . '*');
            if (!empty($keys)) {
                return $this->redis->del($keys);
            }
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors du nettoyage du cache: " . $e->getMessage());
            return false;
        }
    }

    public function exists($key) {
        if (!$this->redis) return false;
        
        try {
            return $this->redis->exists($this->prefix . $key);
        } catch (Exception $e) {
            error_log("Erreur lors de la vérification du cache: " . $e->getMessage());
            return false;
        }
    }
} 
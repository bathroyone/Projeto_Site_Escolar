<?php
/**
 * Sistema de Cache para Melhorar Performance
 * 
 * Implementa cache em arquivo para reduzir consultas ao banco de dados
 */

class CacheManager {
    private $cacheDir;
    private $defaultTTL;
    
    public function __construct($cacheDir = null, $defaultTTL = 3600) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/cache';
        $this->defaultTTL = $defaultTTL;
        
        // Criar diretório de cache se não existir
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Gerar chave de cache segura
     */
    private function generateKey($key) {
        return md5($key) . '.cache';
    }
    
    /**
     * Obter caminho do arquivo de cache
     */
    private function getCachePath($key) {
        return $this->cacheDir . '/' . $this->generateKey($key);
    }
    
    /**
     * Salvar dados no cache
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $path = $this->getCachePath($key);
        
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        file_put_contents($path, serialize($cacheData), LOCK_EX);
        return true;
    }
    
    /**
     * Obter dados do cache
     */
    public function get($key) {
        $path = $this->getCachePath($key);
        
        if (!file_exists($path)) {
            return null;
        }
        
        $cacheData = unserialize(file_get_contents($path));
        
        // Verificar se expirou
        if ($cacheData['expires'] < time()) {
            $this->delete($key);
            return null;
        }
        
        return $cacheData['data'];
    }
    
    /**
     * Verificar se chave existe no cache
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Deletar chave do cache
     */
    public function delete($key) {
        $path = $this->getCachePath($key);
        
        if (file_exists($path)) {
            unlink($path);
            return true;
        }
        
        return false;
    }
    
    /**
     * Limpar todo o cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Limpar cache expirado
     */
    public function clearExpired() {
        $files = glob($this->cacheDir . '/*.cache');
        $now = time();
        $cleared = 0;
        
        foreach ($files as $file) {
            $cacheData = unserialize(file_get_contents($file));
            
            if ($cacheData['expires'] < $now) {
                unlink($file);
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Obter ou definir cache (pattern cache-aside)
     */
    public function remember($key, $callback, $ttl = null) {
        $data = $this->get($key);
        
        if ($data !== null) {
            return $data;
        }
        
        $data = $callback();
        $this->set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Obter estatísticas do cache
     */
    public function getStats() {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $expiredCount = 0;
        $validCount = 0;
        $now = time();
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            $cacheData = unserialize(file_get_contents($file));
            
            if ($cacheData['expires'] < $now) {
                $expiredCount++;
            } else {
                $validCount++;
            }
        }
        
        return [
            'total_files' => count($files),
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2),
            'valid_count' => $validCount,
            'expired_count' => $expiredCount
        ];
    }
}

// Instância global
$cacheManager = new CacheManager();

// Funções helper
function cache_get($key) {
    global $cacheManager;
    return $cacheManager->get($key);
}

function cache_set($key, $data, $ttl = null) {
    global $cacheManager;
    return $cacheManager->set($key, $data, $ttl);
}

function cache_remember($key, $callback, $ttl = null) {
    global $cacheManager;
    return $cacheManager->remember($key, $callback, $ttl);
}

function cache_delete($key) {
    global $cacheManager;
    return $cacheManager->delete($key);
}

function cache_clear() {
    global $cacheManager;
    return $cacheManager->clear();
}
?>

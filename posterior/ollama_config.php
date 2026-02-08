<?php
class OllamaAIService {
    private $api_url;
    private $model;
    private $timeout;
    
    public function __construct() {
        // Configuración para Ollama
        $this->api_url = 'http://localhost:11434/api/generate';
        $this->model = 'gpt-oss:120b-cloud'; // o el modelo que tengas instalado
        $this->timeout = 30;
    }
    
    public function consultarIA($prompt, $contexto = '', $max_tokens = 1000) {
        try {
            $payload = [
                'model' => $this->model,
                'prompt' => $this->construirPrompt($prompt, $contexto),
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                    'max_tokens' => $max_tokens
                ]
            ];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->api_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => $this->timeout
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($http_code !== 200) {
                throw new Exception("Error en conexión con IA: $http_code - $error");
            }
            
            $data = json_decode($response, true);
            
            if (!isset($data['response'])) {
                throw new Exception("Respuesta inválida de la IA");
            }
            
            return [
                'success' => true,
                'respuesta' => trim($data['response']),
                'modelo' => $this->model,
                'tokens_usados' => $data['eval_count'] ?? 0
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'respuesta' => 'No se pudo conectar con el servicio de IA. Error: ' . $e->getMessage()
            ];
        }
    }
    
    private function construirPrompt($consulta, $contexto) {
        $prompt = "Eres un asistente IA especializado en gestión empresarial ERP-CRM. ";
        $prompt .= "Proporciona respuestas útiles, prácticas y orientadas a la acción.\n\n";
        
        if (!empty($contexto)) {
            $prompt .= "CONTEXTO ACTUAL:\n$contexto\n\n";
        }
        
        $prompt .= "CONSULTA DEL USUARIO:\n$consulta\n\n";
        $prompt .= "RESPUESTA ESPECIALIZADA:";
        
        return $prompt;
    }
    
    public function analizarSentimiento($texto) {
        $prompt = "Analiza el sentimiento del siguiente texto y clasifícalo como: POSITIVO, NEGATIVO o NEUTRAL. ";
        $prompt .= "Justifica brevemente tu clasificación.\n\nTEXTO: \"$texto\"";
        
        return $this->consultarIA($prompt, '', 500);
    }
    
    public function sugerirCategoriaTarea($descripcionTarea) {
        $prompt = "Basándote en la siguiente descripción de tarea, sugiere la categoría más apropiada: ";
        $prompt .= "Por hacer, En progreso, En revisión o Hecho. Justifica brevemente.\n\n";
        $prompt .= "DESCRIPCIÓN: \"$descripcionTarea\"";
        
        return $this->consultarIA($prompt, '', 300);
    }
    
    public function generarResumenActividades($actividades) {
        $contexto = "Lista de actividades realizadas:\n" . implode("\n", $actividades);
        $prompt = "Genera un resumen ejecutivo de estas actividades, destacando los logros principales ";
        $prompt .= "y sugerencias para mejorar la productividad.";
        
        return $this->consultarIA($prompt, $contexto, 800);
    }
}
?>
<?php
namespace App\Models;

use PDO;

class ChatSessionModel extends BaseModel
{
    public function getSession(string $sessionId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM chat_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session && !empty($session['history'])) {
            $session['history'] = json_decode($session['history'], true);
        } else {
            $session = ['session_id' => $sessionId, 'history' => []];
        }
        return $session;
    }

    public function saveSession(string $sessionId, array $history, ?string $userId = null): void
    {
        $jsonHistory = json_encode($history, JSON_UNESCAPED_UNICODE);
        
        $sql = "INSERT INTO chat_sessions (session_id, user_id, history, updated_at) 
                VALUES (:sid, :uid, :hist, NOW())
                ON DUPLICATE KEY UPDATE history = VALUES(history), user_id = VALUES(user_id), updated_at = NOW()";
        
        $this->pdo->prepare($sql)->execute([
            'sid' => $sessionId,
            'uid' => $userId,
            'hist' => $jsonHistory
        ]);
    }
}
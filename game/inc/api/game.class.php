<?php
class SkarbGame
{

    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['key_user']) || empty($_SESSION['key_user'])) $_SESSION['key_user'] = md5(microtime() . '_skarb_user');
        if (!isset($_SESSION['key_room']) || empty($_SESSION['key_room'])) $_SESSION['key_room'] = md5(microtime() . '_skarb_room');
        $this->key_user = $_SESSION['key_user'];
        $this->key_room = $_SESSION['key_room'];
    }
    
    
    

    
    public function initApiSk($arr = []) 
    {        
        switch ($arr['command']) {
            case 'start_over':
                $response = $this->startOverGameSk($arr);
            break;
            case 'set_cell_value':
                $response = $this->setCellValueSk($arr);
            break;
            case 'check_enable_room':
                $response = $this->checkEnableRoomSk();
            break;
        }
        return (isset($response)) ? $response : $this->returnAnswerSk();
    }
    
    
    
    
    
    public function startOverGameSk($arr = []) 
    {
        unset($_SESSION['key_room']);
        return $this->returnAnswerSk(true, 'success');
    }
    
    
    
    
    
    public function checkEnableRoomSk() 
    {
        if (!$this->createConnectDB()) return $this->returnAnswerSk();
        $q = $this->pdo->query('SELECT id, cells_user, cells_bot, active FROM hfyljv_list_rooms WHERE key_room = \'' . $this->key_room . '\'');
        $data_room = $q->fetch(PDO::FETCH_ASSOC);
        
        $response = $this->getCountWinSk();
        
        if (isset($data_room['id'])) {
            if (empty($data_room['active'])) {
                unset($_SESSION['key_room']);
            } else {
                $list_cell_user = (!empty($data_room['cells_user'])) ? json_decode($data_room['cells_user'], 1) : [];
                $list_cell_bot = (!empty($data_room['cells_bot'])) ? json_decode($data_room['cells_bot'], 1) : [];
                $response = array_merge($response, ['list_cell_user' => $list_cell_user, 'list_cell_bot' => $list_cell_bot]);
            }
        }
        
        return $this->returnAnswerSk(true, 'sucess', '', $response);
    }
    
    
    
    
    
    public function setCellValueSk($arr = []) 
    {
        if (!isset($arr['idx']) || !in_array($arr['idx'], [1, 2, 3, 4, 5, 6, 7, 8, 9])) return $this->returnAnswerSk(false, 'error', 'Укажите корректную ячейку', null);
        $arr['idx'] = (int) $arr['idx'];
        if (!$this->createConnectDB()) return $this->returnAnswerSk();
        
        $list_cell_disabled = [];
        $list_cell_user = [];
        $list_cell_bot = [];
        
        $q = $this->pdo->query('SELECT id, cells_user, cells_bot, active FROM hfyljv_list_rooms WHERE key_room = \'' . $this->key_room . '\'');
        $data_room = $q->fetch(PDO::FETCH_ASSOC);
    
        if (!isset($data_room['id'])) {
            $id_room = $this->createNewRoomSk($arr['idx']);
        } else {
            if (empty($data_room['active'])) return $this->returnAnswerSk(false, 'error', 'Игра закончена', null);
            if (!empty($data_room['cells_user'])) {
                $list_cell_user = json_decode($data_room['cells_user'], 1);
                $list_cell_disabled = array_merge($list_cell_disabled, $list_cell_user);
            }
            if (!empty($data_room['cells_bot'])) {
                $list_cell_bot = json_decode($data_room['cells_bot'], 1);
                $list_cell_disabled = array_merge($list_cell_disabled, $list_cell_bot);
            }
            if (in_array($arr['idx'], $list_cell_disabled)) return $this->returnAnswerSk(false, 'error', 'Ячейка занята', null);
            $id_room = $data_room['id'];
        }
        
        $list_cell_user[] = $arr['idx'];
        $list_cell_disabled[] = $arr['idx'];
        
        if ($list_cell_user >= 2 && $user_win = $this->checkWinSk($list_cell_user)) {
            $this->updateRoomSk(['id' => $id_room, 'cells_user' => $list_cell_user, 'cells_bot' => $list_cell_bot, 'win_user' => 1, 'active' => 0]);
            return $this->returnAnswerSk(true, 'user_win', '', ['list_win_cells' => $user_win]);
        }
        
        if ($bot_cell = $this->getFreeCellSk($list_cell_disabled)) {
            $list_cell_bot[] = $bot_cell;
            if ($list_cell_bot >= 2 && $bot_win = $this->checkWinSk($list_cell_bot)) {
                $this->updateRoomSk(['id' => $id_room, 'cells_user' => $list_cell_user, 'cells_bot' => $list_cell_bot, 'win_user' => 0, 'active' => 0]);
                return $this->returnAnswerSk(true, 'bot_win', '', ['bot_cell' => $bot_cell, 'list_win_cells' => $bot_win]);
            }
            $this->updateRoomSk(['id' => $id_room, 'cells_user' => $list_cell_user, 'cells_bot' => $list_cell_bot, 'win_user' => 0, 'active' => 1]);
            return $this->returnAnswerSk(true, 'continue_game', '', ['bot_cell' => $bot_cell]);
        }
        
        $this->updateRoomSk(['id' => $id_room, 'cells_user' => $list_cell_user, 'cells_bot' => $list_cell_bot, 'win_user' => 0, 'active' => 0]);
        return $this->returnAnswerSk(true, 'bot_win'); // ничья. Очко противнику
    }
    
    
    

    
    public function getCountWinSk() 
    {
        if (!$this->createConnectDB()) return $this->returnAnswerSk();
        $win_user = 0;
        $win_bot = 0;
        
        $q = $this->pdo->query('SELECT win_user FROM hfyljv_list_rooms WHERE key_user = \'' . $this->key_user . '\'');
        $data_user = $q->fetchAll(PDO::FETCH_ASSOC);
    
        if (!empty($data_user)) {
            foreach ($data_user as $val) {
                if (!empty($val['win_user'])) $win_user++;
            }
            $win_bot = count($data_user) - $win_user;
        }
        
        return ['win_bot' => $win_bot, 'win_user' => $win_user];
    }
    
    
    
    
    
    public function checkWinSk($list_cells = []) 
    {
        $winner_list = [[1,2,3], [4,5,6], [7,8,9], [1,4,7], [2,5,8], [3,6,9], [1,5,9], [3,5,7]];
    
        foreach ($winner_list as $key => $winner) {
            $i = 0;
            foreach ($winner as $number) {
                if (!in_array($number, $list_cells)) break;
                if (++$i == 3) return $winner_list[$key];
            }
        }
    }
    
    
    
    
    
    public function updateRoomSk($arr = []) 
    {
        if (!empty($arr)) {
            $data = [
                'id' => $arr['id'],
                'cells_user' => json_encode($arr['cells_user']),
                'cells_bot' => json_encode($arr['cells_bot']),
                'win_user' => $arr['win_user'],
                'active' => $arr['active'],
            ];
            $sql = 'UPDATE hfyljv_list_rooms SET cells_user=:cells_user, cells_bot=:cells_bot, win_user=:win_user, active=:active WHERE id=:id';
            $this->pdo->prepare($sql)->execute($data);
        }
    }
    
    
    
    
    
    public function createNewRoomSk($idx = 0) 
    {
        $data = [
            'key_user' => $this->key_user,
            'key_room' => $this->key_room,
            'cells_user' => json_encode((array) $idx),
            'created' => time(),
        ];
        $sql = 'INSERT INTO hfyljv_list_rooms (key_user, key_room, cells_user, created) VALUES (:key_user, :key_room, :cells_user, :created)';
        $this->pdo->prepare($sql)->execute($data);
        return $this->pdo->lastInsertId();
    }
    
    
    
    
    
    public function getFreeCellSk($list_cell_disabled = []) 
    {
        $list_cell = [1, 2, 3, 4, 5, 6, 7, 8, 9];
        
        $tmp_list = array_diff($list_cell, $list_cell_disabled);
        if (!empty($tmp_list)) return $list_cell[array_rand($tmp_list)];
        
        return false;
    }
    
    
    
    
    
    public function createConnectDB() 
    {
        $db_name = 'admin_tictac';
        $db_user = 'admin_gamer';
        $pass = 'MlvBKGtwPV';
        try {
            $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=' . $db_name, $db_user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return true;
        } catch (PDOException $ex) {
            return false;
        }
    }
    
    
    
    
    
    public function returnAnswerSk($success = false, $command = 'error', $message = null, $data = []) 
    {
        $response = [
            'success' => $success,
            'command' => $command,
            'message' => $message,
            'data' => $data,
        ];        
        
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}

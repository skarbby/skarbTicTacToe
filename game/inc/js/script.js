$(document).ready(function() 
{
    checkEnableRoomSk();

    $(document).on('click','.cell_block_sk:not(.disabled_skarb)', function(a) {
        let obj = $(this);
        if (!obj.hasClass('disabled_skarb')) {
            obj.addClass('disabled_skarb');
            
            $.post('/game/inc/api/connector.php', {
                command: 'set_cell_value',
                idx: obj.data('idx'),
            },
            function(response) {
                if (response.success) {
                    obj.html('X');
                    switch (response.command) {
                        case 'continue_game':    
                        case 'bot_win':    
                            setTimeout(function() {
                                $('.cell_block_sk[data-idx="' + response.data.bot_cell + '"]').html('O').addClass('disabled_skarb');
                            }, 300);
                            
                            if (response.command == 'bot_win') {
                                $('.button_start_over_sk').removeClass('disabled_skarb');
                                $('.cell_block_sk').addClass('disabled_skarb');
                                if (typeof response.data.list_win_cells !== "undefined" && response.data.list_win_cells !== null && response.data.list_win_cells.length !== 0) {
                                    response.data.list_win_cells.forEach(function callback(idx, index) {
                                        $('.cell_block_sk[data-idx="' + idx + '"]').addClass('red_color_sk');
                                    });
                                }
                                let block_score_bot = $('.block_score_users_sk').eq(1).find('span');
                                block_score_bot.html(+block_score_bot.html() + 1);
                            }
                        break;
                        case 'user_win':    
                            $('.button_start_over_sk').removeClass('disabled_skarb');
                            $('.cell_block_sk').addClass('disabled_skarb');
                                if (typeof response.data.list_win_cells !== "undefined" && response.data.list_win_cells !== null && response.data.list_win_cells.length !== 0) {
                                response.data.list_win_cells.forEach(function callback(idx, index) {
                                    $('.cell_block_sk[data-idx="' + idx + '"]').addClass('white_color_sk');
                                });
                            }
                            let block_score_user = $('.block_score_users_sk').eq(0).find('span');
                            block_score_user.html(+block_score_user.html() + 1);
                        break;
                    }
                } else {
                    alert(response.message);
                }
            }, 'json');
        }
    });
    
    
    $(document).on('click','.button_start_over_sk:not(.disabled_skarb)', function(a) {
        $('.cell_block_sk').html('').removeClass('disabled_skarb white_color_sk red_color_sk');
        
        let obj = $(this);
        if (!obj.hasClass('disabled_skarb')) {
            obj.addClass('disabled_skarb');
            
            $.post('/game/inc/api/connector.php', {
                command: 'start_over',
            });
        }
    });
    
});



function checkEnableRoomSk() {
    $.post('/game/inc/api/connector.php', {
        command: 'check_enable_room',
    },
    function(response) {
        if (response.success) {
            if (response.data.active == '0') {
                $('.button_start_over_sk').removeClass('disabled_skarb');
            }
            if (typeof response.data.list_cell_user !== "undefined" && response.data.list_cell_user !== null && response.data.list_cell_user.length !== 0) {
                response.data.list_cell_user.forEach(function callback(idx, index) {
                    $('.cell_block_sk[data-idx="' + idx + '"]').html('X').addClass('disabled_skarb');
                });
            }
            if (typeof response.data.list_cell_bot !== "undefined" && response.data.list_cell_bot !== null && response.data.list_cell_bot.length !== 0) {
                response.data.list_cell_bot.forEach(function callback(idx, index) {
                    $('.cell_block_sk[data-idx="' + idx + '"]').html('O').addClass('disabled_skarb');
                });
            }
            $('.block_score_users_sk').eq(0).find('span').html(response.data.win_user);
            $('.block_score_users_sk').eq(1).find('span').html(response.data.win_bot);
        }
    }, 'json');
}
(function () {
    'use strict';

    var allUsersInWS = [];
    //get users of this WS
    $.ajax({
        url: Routing.generate('cpasimusante_get_user_in_ws', {wslist:'1,2'}),
        type: 'GET',
        success: function (datas) {
            allUsersInWS = (datas !== null) ? datas : [];
        }
    });
    //pick users
    $('#pick-user-for-stats').on('click', '#pick-user-for-stats-btn', function () {
        var userPicker = new UserPicker();
        var settings = {
            multiple: true,
            picker_name: 'graphuser_picker',
            picker_title: Translator.trans('select_users_to_add_to_graph', {},'resource'),
            whitelist: allUsersInWS,
            return_datas: true,
            selected_users:selectedUsers
        };
        userPicker.configure(settings, addUsersToGraph);
        userPicker.open();
    });
    //callback, add users to widget user field
    var addUsersToGraph = function(users) {
        var userAdded = '';
        var userIds = [];
        if (users !== null) {
            userAdded = '';
            for (var i= 0,tot=users.length;i<tot;i++) {
                userAdded += users[i].lastName+" "+users[i].firstName+"<br>";
                userIds.push(users[i].id);
            }
        }
        $('#pick-user-for-stats .isel_widget').html(userAdded);
        $('#cpasimusante_exoverridebundle_exoverridestatconfig_userlist').val(userIds);
    };

}());
function getdata(e){
    $.getJSON('index.php', {date: $(e).data('date')}, function(data){
        $.each(data, (key, val) => {
            if (val != '') {
                $('#tableStat ' + key).html(val)
            }
        });
    })    
}
function alertdel(url, el) {
    if (confirm('!!! هل أنت متأكد؟ !!!')) {
        el.classList.remove('feather-trash')
        el.classList.add('feather-loader')
        el.classList.add('spining')
        window.location = url
    }
}

function chooseTab(e) {
    var tabsdiv = document.querySelectorAll(".tab")
    var id = e.target.id
    tabsdiv.forEach(el => {
        el.classList.remove('active')
        if (el.id == "tab_" + id) el.classList.add('active')
    });
    var alltabs = e.currentTarget.children
    for (var i = 0; i < alltabs?.length; i++) {
        var el = alltabs[i];
        el.classList.remove('active')
        if (el == e.target) el.classList.add('active')
    }
}


document.addEventListener('DOMContentLoaded', function() {

    // var tables = document.querySelectorAll('table.table')
    // if(tables.length) tables.forEach(function(el){

    // })
    var dmeulists = document.querySelectorAll('.list .dmenu .list')
    dmeulists.forEach(function (el) {
        el.style.left = '-' + el.getBoundingClientRect().width + 'px'
    })

    var tabsselect = document.getElementById("tabsselect")
    if (tabsselect) tabsselect.addEventListener('change', function(e){
        chooseTab({ target: { id: e.target.value }})
    })
    var tabs = document.getElementById("tabs")
    if(tabs) tabs.addEventListener('click', chooseTab)

    var $forms = document.getElementsByTagName('form')
    for (var i = 0; i < $forms.length; i++) {
        var form = $forms[i];
        form.addEventListener('submit', function (e) {
            if (this.id == 'reportform') {
                var hID = this.querySelector('input[name="hotel_id"]')
                if(hID) hID = parseInt(hID.value)
                if(hID == 0) {
                    e.preventDefault() 
                    return alert('لم يتم اختيار بيانات الفندق.')
                }
            }
            var $smbt = this.querySelectorAll('button[type="submit"]')
            if ($smbt.length) {
                form.setAttribute('disabled', true)
                $smbt[0].setAttribute('disabled', true)
                $smbt[0].innerHTML = '&nbsp;<i class="feather-loader text-primary spining"></i>'
            }

            return true
        })
    }


})
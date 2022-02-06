
    function change_field(field, action = 'add', id = null) {
            this.field=field
            this.id=id
            if (action == 'add') {
                var latest_id =Number(document.getElementById(`${this.field}_field_number`).value);
                var new_id = latest_id+1

                //var field = `<div class='form-check' id='${field}_container_${new_id}'><input type='text' class='${this.field}_field' name='${field}_field[]' id='${field}_field_${new_id}'><button class='btn btn-danger' id='delete_${field}_button_${new_id}'type='button'title='delete'onclick="change_field('${field}','delete',${new_id} )"><span class='glyphicon glyphicon-trash'></span></button></div>`;
                
                var container= document.createElement('div')
                container.id=`${field}_container_${new_id}`
                container.classList=`form-group input-group mb-3`


                var input = document.createElement('input')
                input.type='text'
                input.classList=`${this.field}_field form-control`
                input.name=`${field}_field[]`
                input.id=`${field}_field_${new_id}`
                //input.autofocus=true

                var button_container= document.createElement('div')
                button_container.classList=`input-group-append`

                var button=document.createElement('button')
                button.classList=`btn btn-danger`
                button.setAttribute('onclick', `change_field('${field}','delete',${new_id})`)
                button.type=`button`
                button.title='Delete Field'
                button.id=`delete_${field}_button_${new_id}`
                button.innerHTML+='<span class="glyphicon glyphicon-trash">Delete</span>'


                container.appendChild(input)
                button_container.appendChild(button)
                container.appendChild(button_container)
                document.getElementById('fields').appendChild(container)


                document.getElementById(`${this.field}_field_number`).value=new_id
                //document.getElementById('fields').innerHTML += field;

            }
            else {
                if (action = 'delete' && this.id != null) {

                    var field_name = String(`${this.field}_container_${id}`);
                    document.getElementById(field_name).remove();
                    var field_number_input = document.getElementById(`${this.field}_field_number`);
                    var prev_total=Number(field_number_input.value)
                    field_number_input.value--
                    var current_total=Number(field_number_input.value)
                    if(Number(current_total)!=0){

                      for (let i = prev_total; i > this.id; i--) {
                        var field_name=`${this.field}_field_${i}`
                        var current_field=document.getElementById(field_name)
                        var prev_input_id = current_field.id;
                        var prev_field_number = i
                        var current_field_number=i-1;
                        current_field.setAttribute( 'name', `${this.field}_field_${current_field_number}`);
                        current_field.id = `${this.field}_field_${current_field_number}`;
                        document.getElementById(`${this.field}_container_${i}`).id=`${this.field}_container_${current_field_number}`
                        document.getElementById(`delete_${this.field}_button_${i}`).setAttribute('onclick',`change_field('${this.field}', 'delete',${current_field_number})`)
                        document.getElementById(`delete_${this.field}_button_${i}`).id=`delete_${this.field}_button_${current_field_number}`
                    
                    
                      }  
                    }
                }else {
                    r_to_yengas();
                }
            }

            function r_to_yengas() {
                window.location = 'https://www.google.com/search?q=Yengas%20Technologies';
            }
        }


function remove_empty_field(field_input){
    this.field_input=field_input
    if (typeof(this.field_input)=='string'){
        remove_field(this.field_input)
    }else{
        if (Array(field_input)){
            for (let index = 0; index < this.field_input.length; index++) {
                const element = this.field_name[index];
                remove_field(element)
            }
        }

    }

    function remove_field(field_name){
        var expected_total=document.getElementById(`${field_name}_field_number`)
        console.log(expected_total.value)
        for ( i = expected_total.value; i <=0; i--) {
            if(i<=0){
                break;
            }
            var field=document.getElementById(`${field_name}_field_${i}`)
            console.log('thisis the value'+field.value+'today')
            if (field.value.length == 0){
                field.remove()
                expected_total.value--
            }

        }
    }
}



function send_get_request(field) {
    id=document.getElementById(field).value
    document.getElementById('form').setAttribute('method', 'get')
    document.getElementById('form').submit()

}
$(document).ready(function() {
    $('.select_multiple').select2();
});
function addUnit() {
    var name=document.getElementById('unit-name').value
    var symbol=document.getElementById('unit-symbol').value

    if (symbol.length == 0 ||  name.length==0) {
      return;
    } else {
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            console.log(this.responseText)
            response=JSON.parse(this.responseText)
            document.getElementById('unit-name').value=response.name; 
            document.getElementById('unit-symbol').value=response.symbol; 
            document.getElementById('unit-name-err').innerHTML=response.name_err; 
            document.getElementById('unit-symbol-err').innerHTML=response.symbol_err; 
            document.getElementById('status').innerHTML=response.status; 
            if (response.name_err.length>0){
                document.getElementById('unit-name').classList+=' is-invalid'
            }
            if (response.symbol_err.length>0){
                document.getElementById('unit-symbol').classList+=' is-invalid'
            }

            }
      };
      xmlhttp.open("GET", "/forms/add/unit.php?name=" + name+'&symbol='+symbol, true);
      xmlhttp.send();
    }
  }
function get_total_price(columnid=null){
    //console.log('cost_per_unit'+((columnid!==null)?(columnid):""))
    unit_price=document.getElementById('cost_per_unit'+((columnid!==null)?(columnid):"")).value || 0
    quantity=document.getElementById('quantity'+((columnid!==null)?(columnid):"")).value || 0
    total_price=(quantity*10 * (unit_price)*100)/1000
    document.getElementById('amount'+((columnid!==null)?(columnid):"")).value=total_price
}
/*function max_allowed(field,max_value) {
    field.target.setCustomValidity("");
    if (!field.target.validity.valid) {
        field.target.setCustomValidity(`Quantity in store is ${max_value}. Input value annot exceed this`);
    }
    field.oninput = function(field) {
        field.target.setCustomValidity("");
    };
};
*/

if ( window.history.replaceState ) {
    window.history.replaceState( null, null, window.location.href );
  }

function confirm_values(){
    var mx_v=document.getElementById('t_n').value
    document.getElementById('valueslist').innerHTML=""
    for (let i = 0; i < mx_v; i++) {
        field=document.getElementById('product'+i)
        var name=field.getAttribute('data-name')
        var unit=field.getAttribute('data-unit')
        var value=field.value || 0
        var l=`<li>${name}  :${value} ${unit}(s)`
        if (Number(field.getAttribute('data-quantity')) > Number(value)){
            l+="    (Incomplete)"
        }
        l+="</li>"
        document.getElementById('valueslist').innerHTML+=l 
        
    }
    $('#confirmvaluesmodal').modal('show')

}
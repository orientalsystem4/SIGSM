// Validacion en el navegador, comun a todos los formularios.
// Es una comodidad: el servidor valida todo igual y es la autoridad.
//
// Uso: <form data-validar-form> y en cada campo data-validar="regla regla:valor ...".
// Cada regla toma su mensaje de data-msg-REGLA (impreso por PHP con te()).
// Un campo opcional vacio no se valida. Los errores aparecen al salir del campo
// o al enviar, nunca antes de que la persona lo haya tocado.
//
// Reglas:
//   requerido      no vacio (se recorta como trim() de PHP; las contrasenas no)
//   min:N, max:N   largo en caracteres (como mb_strlen)
//   bytesmin:N, bytesmax:N  largo en bytes UTF-8 (como strlen de PHP)
//   patron         expresion de data-patron (completa, con ^ y $)
//   cedula         como normalizarCedula() de ValidacionIdentidad.php
//   nombre         como nombreCompletoValido() de ValidacionIdentidad.php
//   igual:ID       mismo valor que el campo con ese id (se evalua aunque este vacio)
//   grupo          en un contenedor: al menos una casilla marcada
//   futura         fecha y hora posterior al momento actual
//   pdf            archivo con extension .pdf y tipo application/pdf
//   tamano:N       archivo de N bytes como maximo
//   entero:A:B     numero entero entre A y B
(function () {
    'use strict';

    // trim() de PHP: solo espacio, \t, \n, \r, \0 y \x0B
    function trimPhp(texto) {
        return texto.replace(/^[ \t\n\r\0\x0B]+|[ \t\n\r\0\x0B]+$/g, '');
    }

    function largo(texto) {
        return Array.from(texto).length;
    }

    function bytes(texto) {
        return new TextEncoder().encode(texto).length;
    }

    function cedulaValida(texto) {
        texto = trimPhp(texto);
        if (!/^(?:[0-9]{7,8}|[0-9]{1,2}\.[0-9]{3}\.[0-9]{3}-[0-9])$/.test(texto)) {
            return false;
        }
        var digitos = texto.replace(/\D/g, '').padStart(8, '0');
        var pesos = [2, 9, 8, 7, 6, 3, 4];
        var suma = 0;
        for (var i = 0; i < 7; i++) {
            suma += Number(digitos[i]) * pesos[i];
        }
        return (10 - (suma % 10)) % 10 === Number(digitos[7]);
    }

    function nombreValido(texto) {
        var n = largo(texto);
        return n >= 3 && n <= 150 && /^[\p{L}\p{M} .'-]+$/u.test(texto);
    }

    function esGrupo(el) {
        return !('value' in el) || el.tagName === 'FIELDSET';
    }

    function valorDe(el) {
        var valor = String(el.value);
        return el.type === 'password' || el.type === 'file' ? valor : trimPhp(valor);
    }

    // Reglas que se evaluan aunque el campo este vacio
    var siempre = { requerido: true, grupo: true, igual: true };

    var reglas = {
        requerido: function (el, v) { return v !== ''; },
        min: function (el, v, a) { return largo(v) >= Number(a[0]); },
        max: function (el, v, a) { return largo(v) <= Number(a[0]); },
        bytesmin: function (el, v, a) { return bytes(v) >= Number(a[0]); },
        bytesmax: function (el, v, a) { return bytes(v) <= Number(a[0]); },
        patron: function (el, v) { return new RegExp(el.dataset.patron, 'u').test(v); },
        cedula: function (el, v) { return cedulaValida(v); },
        nombre: function (el, v) { return nombreValido(v); },
        igual: function (el, v, a) {
            var otro = document.getElementById(a[0]);
            return !otro || el.value === otro.value;
        },
        grupo: function (el) {
            return el.querySelector('input[type=checkbox]:checked, input[type=radio]:checked') !== null;
        },
        futura: function (el, v) {
            var fecha = new Date(v);
            return !isNaN(fecha.getTime()) && fecha.getTime() > Date.now();
        },
        pdf: function (el) {
            var archivo = el.files && el.files[0];
            return !archivo || (/\.pdf$/i.test(archivo.name) && archivo.type === 'application/pdf');
        },
        tamano: function (el, v, a) {
            var archivo = el.files && el.files[0];
            return !archivo || archivo.size <= Number(a[0]);
        },
        entero: function (el, v, a) {
            if (!/^-?[0-9]+$/.test(v)) {
                return false;
            }
            var n = Number(v);
            return n >= Number(a[0]) && n <= Number(a[1]);
        }
    };

    // Devuelve el mensaje del primer error, o '' si el campo es valido
    function error(el) {
        if (el.disabled) {
            return '';
        }
        var grupo = esGrupo(el);
        var valor = grupo ? '' : valorDe(el);
        var lista = (el.dataset.validar || '').split(/\s+/).filter(Boolean);
        for (var i = 0; i < lista.length; i++) {
            var partes = lista[i].split(':');
            var nombre = partes.shift();
            var regla = reglas[nombre];
            if (!regla || (!grupo && valor === '' && !siempre[nombre])) {
                continue;
            }
            if (!regla(el, valor, partes)) {
                var clave = 'msg' + nombre.charAt(0).toUpperCase() + nombre.slice(1);
                return el.dataset[clave] || el.dataset.msg || '';
            }
        }
        return '';
    }

    function mensajeDe(el) {
        var id = (el.id || el.dataset.validarId || '') + '-validacion';
        var p = el.id ? document.getElementById(id) : null;
        if (!p) {
            p = document.createElement('p');
            p.className = 'validacion-mensaje';
            p.setAttribute('aria-live', 'polite');
            if (el.id) {
                p.id = id;
                var descritos = (el.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
                if (descritos.indexOf(id) === -1) {
                    descritos.push(id);
                    el.setAttribute('aria-describedby', descritos.join(' '));
                }
            }
            if (esGrupo(el)) {
                el.appendChild(p);
            } else {
                el.insertAdjacentElement('afterend', p);
            }
        }
        return p;
    }

    // Valida un campo; solo muestra el mensaje si ya fue tocado
    function validarCampo(el) {
        var texto = error(el);
        if (!esGrupo(el) && el.setCustomValidity) {
            el.setCustomValidity(texto);
        }
        if (el.dataset.validarTocado === '1') {
            if (texto) {
                el.setAttribute('aria-invalid', 'true');
            } else {
                el.removeAttribute('aria-invalid');
            }
            var p = mensajeDe(el);
            p.textContent = texto;
            p.hidden = texto === '';
        }
        return texto === '';
    }

    function campos(form) {
        return Array.prototype.slice.call(form.querySelectorAll('[data-validar]'));
    }

    // Valida todo el formulario; enfoca el primer error. Lo usan tambien los
    // formularios que se envian por fetch (SIGSMValidacion.validar(form)).
    function validar(form) {
        var primero = null;
        campos(form).forEach(function (el) {
            el.dataset.validarTocado = '1';
            if (!validarCampo(el) && !primero) {
                primero = el;
            }
        });
        // Restricciones HTML de los campos sin data-validar (required, maxlength...)
        if (!primero) {
            Array.prototype.forEach.call(form.elements, function (el) {
                if (!primero && !el.dataset.validar && el.willValidate && !el.checkValidity()) {
                    primero = el;
                }
            });
            if (primero) {
                primero.reportValidity();
                return false;
            }
        }
        if (primero) {
            var foco = esGrupo(primero) ? primero.querySelector('input, select, textarea') : primero;
            if (foco) {
                foco.focus();
            }
            return false;
        }
        return true;
    }

    function campoDe(form, objetivo) {
        var el = objetivo.closest('[data-validar]');
        return el && form.contains(el) ? el : null;
    }

    function preparar(form) {
        // Los mensajes los muestra este script, no el globo del navegador
        form.noValidate = true;

        form.addEventListener('focusout', function (e) {
            var el = campoDe(form, e.target);
            // En un grupo, solo cuando el foco sale del grupo entero
            if (el && !(esGrupo(el) && e.relatedTarget && el.contains(e.relatedTarget))) {
                el.dataset.validarTocado = '1';
                validarCampo(el);
            }
        });

        // Al corregir, el error desaparece; y los campos que dependen de otro
        // (confirmar contrasena) se vuelven a revisar
        function revisar() {
            campos(form).forEach(validarCampo);
        }
        form.addEventListener('input', revisar);
        form.addEventListener('change', revisar);

        // En captura: corre antes que otros manejadores de envio del formulario
        form.addEventListener('submit', function (e) {
            if (!validar(form)) {
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        }, true);

        revisar();
    }

    window.SIGSMValidacion = { validar: validar, validarCampo: validarCampo };

    Array.prototype.forEach.call(document.querySelectorAll('form[data-validar-form]'), preparar);
})();

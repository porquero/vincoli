# Vincoli

## ¿Qué es?
Vincoli es una plataforma SaaS que ayuda a organizar y generar horarios de clases, permitiendo a los establecimientos educacionales administrar de manera visual la información curricular del cuerpo docente, utilizando solamente la carga académica de éstos y el plan de estudio de los cursos.

##¿Cómo funciona?
Su uso es visual y lógico. Configurando el plan de estudios de los cursos y sus bloques, la carga académica y la disponibilidad de los profesores, será posible utilizar el editor visual en donde se podrá generar y modificar el horario de cada curso, teniendo un mayor control en el resultado final.

## Características del sistema

* Multiusuario.
* Seguridad en la información.
* Sistema siempre disponible, desde cualquier lugar, 100% web.
* Multiplataforma.
* Horarios de curso y profesor.
* Editor y generador de Horarios.
* Exportación de horarios a formato Excel.
* Sin limitaciones para cantidad de cursos o profesores.

## Simple e intuitivo
✔ Genera el horario sólo utilizando el plan de estudio del curso y la carga académica del profesor, sin configuraciones complicadas.

## Editor de horarios
Vincoli fue echo para trabajar de manera visual haciendo el trabajo del usuario más intuitiva:
* Generador de horario por sistema.
* Editor manual de horario, para tener más control sobre la carga horaria del curso/profesor.
* Resumen que informa qué asignaturas faltan por agregar al horario del curso.
* Desde el primer momento se puede saber en qué días y bloques figuran las asignaturas y profesores.
* Disponibilidad de asignaturas por bloque.
* Listado de profesores disponibles por día y bloque.

![image](https://github.com/porquero/vincoli/assets/1017731/acac916b-350b-4206-a045-1c9f1a5cb972)

## Administración de profesores

![image](https://github.com/porquero/vincoli/assets/1017731/bdf78a2c-f0a7-4d53-b8af-5699ad2e5389)

El editor de horario toma en cuenta la disponibilidad de horario del profesor y asignaturas que faltan para completar el horario.

![image](https://github.com/porquero/vincoli/assets/1017731/2c89ec1b-c329-4b99-8522-29f79c5e5ae4)

Define los cursos que debe tomar el profesor.

![image](https://github.com/porquero/vincoli/assets/1017731/066418df-fd43-4125-945a-120763cfbc93)




![image](https://github.com/porquero/vincoli/assets/1017731/a8cabaa9-af2e-4528-8b52-8b84047b0b47)

Trabaja según la disponibilidad del Profesor.

* Disponibilidad de Profesores
* Cursos de Profesores
* Asignaturas que dicta el Profesor
* Selecciona las asignaturas que dicta el profesor.

## Administración de cursos




* Plan de Estudio del Curso
* Al definir el Plan de Estudio puedes fijar el profesor que dictará la asignatura, si lo deseas.
* Bloques del curso
* Puedes fijar los bloques por cada curso.

# Instalación
* En la raíz crear el archivo: .env con lo siguiente:
```
CI_ENVIRONMENT = development
```
* Luego modificar el archivo de configuración para usar la base de datos: /application/config/development/database.php
* Cargar los datos de prueba utilizando el archivo: vincoli.sql

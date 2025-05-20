
# Sistema de Gestión y Aprobación de Órdenes de Compra

Este repositorio contiene una plataforma web para digitalizar y gestionar el ciclo completo de Órdenes de Compra (O.C.), permitiendo su registro, aprobación, rechazo, seguimiento y notificaciones automáticas, con historial detallado de cada proceso.

## Características principales

- **Gestión de usuarios y roles:** Soporte para Gestores, Aprobadores de Área, Aprobadores Generales y Visualizadores.
- **Registro y seguimiento de O.C.:** Los gestores pueden crear nuevas órdenes de compra y consultar el estado de sus solicitudes.
- **Flujo de aprobación:** Proceso de aprobación en dos etapas (Área y General), con notificaciones automáticas por correo electrónico.
- **Historial y bitácora:** Registro completo de todas las acciones, comentarios y cambios de estado para garantizar la trazabilidad.
- **Notificaciones automáticas:** Envío de correos electrónicos a los usuarios involucrados en cada etapa.

## Estructura del sistema

El sistema está basado en las siguientes entidades principales:

- **Usuario:** Persona que interactúa con el sistema, con un rol y un área asignados.
- **Área:** Departamento o sección a la que pertenece un usuario o una O.C.
- **OrdenCompra:** Representa una orden de compra, con su información y estado actual.
- **EstadoOC:** Historial de estados por los que pasa una O.C.
- **Comentario:** Observaciones o motivos en cada etapa del proceso.
- **Notificación:** Mensajes automáticos enviados a los usuarios.
- **Bitácora:** Registro de auditoría de todas las acciones realizadas.

Puedes consultar el diagrama de clases en [`UML/diagrama_clases.puml`](UML/diagrama_clases.puml) para una visión detallada de la estructura y relaciones del sistema.

---

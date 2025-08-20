<?php 
require 'fpdf/fpdf.php'; 
require 'db.php'; 
session_start(); 

if(!isset($_SESSION['user_id'])) die("Acceso denegado"); 
if(!isset($_GET['id'])) die("ID de ticket no especificado"); 

$id = (int)$_GET['id']; 

$query="SELECT t.*, u.nombre_completo AS solicitante, tec.nombre_completo AS tecnico 
        FROM tickets t 
        JOIN users u ON t.user_id=u.id 
        LEFT JOIN users tec ON t.tecnico_id=tec.id 
        WHERE t.id=?"; 

$stmt=$pdo->prepare($query); 
$stmt->execute([$id]); 
$ticket=$stmt->fetch(PDO::FETCH_ASSOC); 

if(!$ticket) die("Ticket no encontrado"); 

class PDF extends FPDF {
    function Header(){
        // Logo
        $this->Image('logo.png',10,8,25); // pon tu logo en la carpeta
        // Fuente y título
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,utf8_decode('UNIVERSIDAD FRANCISCO DE PAULA SANTANDER'),0,1,'C');
        $this->SetFont('Arial','',11);
        $this->Cell(0,10,utf8_decode('Informe Técnico de Soporte - Departamento de Sistemas'),0,1,'C');
        $this->Ln(5);
        // Línea roja
        $this->SetDrawColor(188,0,23);
        $this->SetLineWidth(1);
        $this->Line(10,35,200,35);
        $this->Ln(10);
    }

    function Footer(){
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,utf8_decode('Página ').$this->PageNo().'/{nb}',0,0,'C');
    }

    function Field($label, $value){
        $this->SetFont('Arial','B',10);
        $this->Cell(45,8,utf8_decode($label),0,0);
        $this->SetFont('Arial','',10);
        $this->MultiCell(0,8,utf8_decode($value));
        $this->Ln(2);
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Título del ticket
//$pdf->SetFont('Arial','B',12);
//$pdf->SetTextColor(188,0,23);
//$pdf->Cell(0,10,'Ticket N° '.$ticket['id'],0,1,'C');
//$pdf->Ln(5);
//$pdf->SetTextColor(0,0,0);

// Información del ticket
$pdf->Field('Solicitante:', $ticket['solicitante']);
$pdf->Field('Dependencia:', $ticket['nombre_dependencia']);
$pdf->Field('Ubicación:', $ticket['ubicacion']);
$pdf->Field('Equipo:', $ticket['equipo']);
$pdf->Field('Marca/Modelo:', $ticket['marca_modelo']);
$pdf->Field('Inventario/Serial:', $ticket['numero_inventario']);
$pdf->Field('Descripción:', $ticket['descripcion']);
$pdf->Field('Solución:', $ticket['solucion']);
$pdf->Field('Técnico:', $ticket['tecnico'] ?? '-');
$pdf->Field('Estado:', $ticket['estado']);

// Firma si existe
if($ticket['firmado']){
    $pdf->Ln(10);
    $pdf->SetFont('Arial','I',10);
    $pdf->MultiCell(0,8,utf8_decode('Firmado electrónicamente por '.$ticket['tecnico'].' el '.$ticket['fecha_terminacion']));
}

// Línea final
$pdf->Ln(15);
$pdf->SetDrawColor(188,0,23);
$pdf->SetLineWidth(0.7);
$pdf->Line(10,$pdf->GetY(),200,$pdf->GetY());
$pdf->Ln(8);
$pdf->SetFont('Arial','',9);
$pdf->Cell(0,8,utf8_decode('Sistema de Tickets - UFPS'),0,1,'C');

$pdf->Output('I','Ticket_'.$ticket['id'].'.pdf'); 
?>

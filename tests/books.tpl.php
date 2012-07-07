<html>

<head>
	<title><?php echo $this->eprint($this->title); ?></title>
</head>

<body>

<?php if (is_array($this->books)): ?>

<!-- A table of some books. -->
<table>
	<tr>
		<th>Author</th>
		<th>Title</th>
	</tr>

	<?php foreach ($this->books as $key => $val): ?>
	<tr>
		<td><?php echo $this->eprint($val['author']); ?></td>
		<td><?php echo $this->eprint($val['title']); ?></td>
	</tr>
	<?php endforeach; ?>

</table>

	<?php else: ?>

<p>There are no books to display.</p>

	<?php endif; ?>

</body>
</html>
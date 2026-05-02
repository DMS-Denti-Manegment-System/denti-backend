import React from 'react'
import { Form, Input, Button, Select, Card } from 'antd'
import { PlusOutlined, EditOutlined } from '@ant-design/icons'
import { useTodos } from '../Hooks/useTodos'
import { useCategories } from '@/Modules/category/Hooks/useCategories'
import { CreateTodoRequest, Todo } from '../Types/todo.types'

const { TextArea } = Input
const { Option } = Select

interface TodoFormProps {
  todo?: Todo
  onSuccess?: () => void
}

export const TodoForm: React.FC<TodoFormProps> = ({ todo, onSuccess }) => {
  const [form] = Form.useForm()
  const { createTodo, updateTodo, isCreating, isUpdating } = useTodos()
  const { categories } = useCategories()

  React.useEffect(() => {
    if (todo) {
      form.setFieldsValue({
        title: todo.title,
        description: todo.description,
        category_id: todo.category_id
      })
    }
  }, [todo, form])

  const onFinish = async (values: CreateTodoRequest) => {
    try {
      if (todo) {
        await updateTodo({ id: todo.id, data: values })
      } else {
        await createTodo(values)
        form.resetFields()
      }
      onSuccess?.()
    } catch (error) {
      console.error('Todo işlemi başarısız:', error)
    }
  }

  return (
    <Card 
      title={todo ? 'Todo Düzenle' : 'Yeni Todo Ekle'} 
      style={{ marginBottom: 24 }}
    >
      <Form
        form={form}
        layout="vertical"
        onFinish={onFinish}
        autoComplete="off"
      >
        <Form.Item
          label="Başlık"
          name="title"
          rules={[
            { required: true, message: 'Başlık gereklidir!' },
            { min: 3, message: 'Başlık en az 3 karakter olmalıdır!' },
            { max: 255, message: 'Başlık en fazla 255 karakter olabilir!' }
          ]}
        >
          <Input placeholder="Todo başlığını girin" />
        </Form.Item>

        <Form.Item
          label="Açıklama"
          name="description"
          rules={[
            { max: 1000, message: 'Açıklama en fazla 1000 karakter olabilir!' }
          ]}
        >
          <TextArea 
            rows={3} 
            placeholder="Todo açıklamasını girin (opsiyonel)" 
          />
        </Form.Item>

        <Form.Item
          label="Kategori"
          name="category_id"
        >
          <Select 
            placeholder="Kategori seçin (opsiyonel)"
            allowClear
          >
            {categories?.map((category: any) => (
              <Option key={category.id} value={category.id}>
                {category.name}
              </Option>
            ))}
          </Select>
        </Form.Item>

        <Form.Item>
          <Button 
            type="primary" 
            htmlType="submit" 
            icon={todo ? <EditOutlined /> : <PlusOutlined />}
            loading={isCreating || isUpdating}
          >
            {todo ? 'Güncelle' : 'Todo Ekle'}
          </Button>
        </Form.Item>
      </Form>
    </Card>
  )
}
package com.example.englishvocab.repository;

import com.example.englishvocab.entity.Question;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;

public interface QuestionRepository extends JpaRepository<Question, Integer> {
    List<Question> findByQuiz_QuizID(Integer quizId);
}
